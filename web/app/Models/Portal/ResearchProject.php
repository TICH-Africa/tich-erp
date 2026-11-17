<?php

namespace App\Models\Portal;

use App\Models\Concerns\PrunesStoredFiles;
use App\Models\Staff;
use App\Services\StoredFileService;
use App\Support\LogoTone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ResearchProject extends Model
{
    use PrunesStoredFiles;

    protected $table = 'research_projects';

    /** @var array<string, string> */
    protected array $storedFiles = [
        'cover_image_path' => 'public',
    ];

    public $timestamps = false;

    protected $fillable = [
        'title', 'slug', 'subtitle', 'status', 'status_locked', 'focus_area_id', 'summary', 'abstract', 'body',
        'project_url', 'objective',
        'cover_image_path', 'bg_color', 'start_date', 'duration_value', 'duration_unit', 'end_date',
        'lead_researcher_id', 'is_featured', 'visibility', 'published_at',
        'created_by', 'updated_by', 'created_at', 'updated_at',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'status_locked' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'duration_value' => 'integer',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(ResearchProjectDocument::class, 'research_project_id')->orderBy('sort_order')->orderBy('id');
    }

    public function leadResearcher(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'lead_researcher_id');
    }

    public function scopePublished($query)
    {
        return $query->where('visibility', 'published');
    }

    public function isPublished(): bool
    {
        return $this->visibility === 'published';
    }

    public function coverUrl(): ?string
    {
        if (! $this->cover_image_path) {
            return null;
        }

        if (str_starts_with($this->cover_image_path, 'http://')
            || str_starts_with($this->cover_image_path, 'https://')) {
            return $this->cover_image_path;
        }

        return asset(ltrim($this->cover_image_path, '/'));
    }

    /**
     * A cover is shown whole, so the page that opens it reads as part of the picture: a white cover wants a white
     * page, a grey one a grey page. The officer picks it on the form; it starts life sampled from the upload.
     */
    public function pageColor(): ?string
    {
        return Schema::hasColumn($this->getTable(), 'bg_color') ? $this->getAttribute('bg_color') : null;
    }

    public function backgroundIsDark(): bool
    {
        return LogoTone::isDark($this->pageColor());
    }

    /**
     * Reads the colour off the stored cover, for studies posted before the picker existed.
     */
    public function syncCoverTone(): void
    {
        if (! Schema::hasColumn($this->getTable(), 'bg_color') || $this->getAttribute('bg_color') || ! $this->cover_image_path) {
            return;
        }

        $binary = $this->coverBinary();
        $tone = $binary !== null ? LogoTone::fromBinary($binary) : null;

        if ($tone !== null) {
            $this->forceFill(['bg_color' => $tone['hex']])->save();
        }
    }

    private function coverBinary(): ?string
    {
        $relative = app(StoredFileService::class)->relativePath((string) $this->cover_image_path);

        if (! $relative || ! Storage::disk('public')->exists($relative)) {
            return null;
        }

        return Storage::disk('public')->get($relative);
    }

    public function statusLabel(): string
    {
        // A project the Research portal shows is live work, so it reads Active rather than Upcoming.
        if ($this->isPublished() && in_array($this->status, ['upcoming', 'ongoing'], true)) {
            return 'Active';
        }

        return match ($this->status) {
            'upcoming' => 'Upcoming',
            'ongoing' => 'Ongoing',
            'completed' => 'Completed',
            'paused' => 'Paused',
            default => ucfirst((string) $this->status),
        };
    }

    /**
     * The objective is stored one point per line.
     *
     * @return list<string>
     */
    public function objectivePoints(): array
    {
        $lines = preg_split('/\R/', (string) $this->objective) ?: [];

        return array_values(array_filter(
            array_map(fn (?string $line): string => trim((string) $line), $lines),
            fn (string $line): bool => $line !== '',
        ));
    }

    public function durationLabel(): string
    {
        return $this->duration_value
            ? $this->duration_value.' '.$this->duration_unit
            : 'Unspecified';
    }

    public function expectedCompletionLabel(): string
    {
        return $this->end_date?->format('d M Y') ?? 'Unspecified';
    }
}

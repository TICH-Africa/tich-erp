<?php

namespace App\Models\Portal;

use App\Models\Concerns\PrunesStoredFiles;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'cover_image_path', 'start_date', 'duration_value', 'duration_unit', 'end_date',
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

    public function statusLabel(): string
    {
        return match ($this->status) {
            'upcoming' => 'Upcoming',
            'ongoing' => 'Ongoing',
            'completed' => 'Completed',
            'paused' => 'Paused',
            default => ucfirst((string) $this->status),
        };
    }
}

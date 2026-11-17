<?php

namespace App\Models\Portal;

use App\Models\Concerns\PrunesStoredFiles;
use App\Services\StoredFileService;
use App\Support\LogoTone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ResearchPartner extends Model
{
    use PrunesStoredFiles;

    protected $table = 'research_partners';

    /** @var array<string, string> */
    protected array $storedFiles = [
        'logo_path' => 'public',
    ];

    protected $fillable = [
        'name', 'logo_path', 'is_active', 'created_by', 'updated_by', 'created_at', 'updated_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // A logo-only partner still needs a label wherever the list names it.
    public function displayLabel(): string
    {
        return $this->name ?: 'Logo only';
    }

    public function logoUrl(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        if (str_starts_with($this->logo_path, 'http://')
            || str_starts_with($this->logo_path, 'https://')) {
            return $this->logo_path;
        }

        return asset(ltrim($this->logo_path, '/'));
    }

    /**
     * The colour the logo paints its own edges with, so the card behind it matches the mark instead of
     * framing a grey or navy banner as a rectangle on white. A blank card means the logo was never read.
     */
    public function syncLogoTone(): void
    {
        if (! Schema::hasColumn($this->getTable(), 'bg_color')) {
            return;
        }

        $tone = $this->logo_path ? LogoTone::fromBinary($this->logoBinary() ?? '') : null;
        $next = $this->logo_path ? ($tone['hex'] ?? LogoTone::WHITE) : null;

        if ($next !== $this->getAttribute('bg_color')) {
            $this->forceFill(['bg_color' => $next])->save();
        }
    }

    public function backgroundColor(): string
    {
        return $this->getAttribute('bg_color') ?: LogoTone::WHITE;
    }

    public function backgroundIsDark(): bool
    {
        return LogoTone::isDark($this->getAttribute('bg_color'));
    }

    private function logoBinary(): ?string
    {
        $relative = app(StoredFileService::class)->relativePath((string) $this->logo_path);

        if (! $relative || ! Storage::disk('public')->exists($relative)) {
            return null;
        }

        return Storage::disk('public')->get($relative);
    }
}

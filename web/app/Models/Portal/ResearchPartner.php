<?php

namespace App\Models\Portal;

use App\Models\Concerns\PrunesStoredFiles;
use Illuminate\Database\Eloquent\Model;

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
}

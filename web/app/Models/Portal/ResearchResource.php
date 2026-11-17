<?php

namespace App\Models\Portal;

use App\Models\Concerns\PrunesStoredFiles;
use Illuminate\Database\Eloquent\Model;

class ResearchResource extends Model
{
    use PrunesStoredFiles;

    protected $table = 'research_resources';

    /** @var array<string, string> */
    protected array $storedFiles = [
        'file_path' => 'public',
    ];

    protected $fillable = [
        'title', 'authors', 'publisher', 'year', 'isbn', 'doi', 'description',
        'file_path', 'original_name', 'mime_type', 'is_active',
        'created_by', 'updated_by', 'created_at', 'updated_at',
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

    public function hasFile(): bool
    {
        return filled($this->file_path);
    }
}

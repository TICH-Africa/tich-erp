<?php

namespace App\Models\Portal;

use App\Models\Concerns\PrunesStoredFiles;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResearchProjectDocument extends Model
{
    use PrunesStoredFiles;

    protected $table = 'research_project_documents';

    public $timestamps = false;

    /** @var array<string, string> */
    protected array $storedFiles = [
        'file_path' => 'local',
    ];

    protected $fillable = [
        'research_project_id', 'title', 'file_path', 'original_filename',
        'mime_type', 'file_size', 'sort_order', 'created_at', 'created_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(ResearchProject::class, 'research_project_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    public function isPdf(): bool
    {
        $mime = strtolower((string) $this->mime_type);
        $ext = strtolower(pathinfo((string) $this->original_filename, PATHINFO_EXTENSION));

        return str_contains($mime, 'pdf') || $ext === 'pdf';
    }

    public function isImage(): bool
    {
        return str_starts_with(strtolower((string) $this->mime_type), 'image/');
    }
}

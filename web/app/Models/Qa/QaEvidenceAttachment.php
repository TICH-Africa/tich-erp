<?php

namespace App\Models\Qa;

use App\Models\Concerns\PrunesStoredFiles;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QaEvidenceAttachment extends Model
{
    use PrunesStoredFiles;

    protected $table = 'qa_evidence_attachments';

    public $timestamps = false;

    /** @var array<string, string> */
    protected array $storedFiles = [
        'file_path' => 'public',
    ];

    protected $fillable = [
        'evidence_type', 'linked_id', 'file_path', 'file_type',
        'description', 'uploaded_by', 'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function uploadedByStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'uploaded_by');
    }

    public function url(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        if (str_starts_with($this->file_path, 'http://') || str_starts_with($this->file_path, 'https://')) {
            return $this->file_path;
        }

        $path = ltrim($this->file_path, '/');
        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        return asset('storage/'.$path);
    }
}

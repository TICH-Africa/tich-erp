<?php

namespace App\Models;

use App\Models\Concerns\PrunesStoredFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationDocument extends Model
{
    use PrunesStoredFiles;

    protected $table = 'application_documents';

    /** @var array<string, string> */
    protected array $storedFiles = [
        'file_path' => 'local',
    ];

    public $timestamps = false;

    protected $fillable = [
        'applicant_id',
        'document_type',
        'file_path',
        'original_filename',
        'mime_type',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class, 'applicant_id');
    }

    public function displayLabel(): string
    {
        return config(
            "tich-application.document_types.{$this->document_type}",
            str_replace('_', ' ', ucfirst($this->document_type))
        );
    }

    public function safeFilename(): string
    {
        $name = preg_replace('/[^\w.\-() ]+/u', '_', $this->original_filename) ?: 'document';

        return $name;
    }

    public function isPreviewable(): bool
    {
        return str_starts_with($this->mime_type, 'image/')
            || $this->mime_type === 'application/pdf';
    }
}

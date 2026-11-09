<?php

namespace App\Models;

use App\Models\Concerns\PrunesStoredFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentApplicationDocumentSubmission extends Model
{
    use PrunesStoredFiles;

    protected $table = 'student_application_document_submissions';

    /** @var array<string, string> */
    protected array $storedFiles = [
        'file_path' => 'local',
    ];

    protected $fillable = [
        'student_id',
        'applicant_id',
        'application_document_id',
        'document_type',
        'file_path',
        'original_filename',
        'mime_type',
        'status',
        'student_notes',
        'reviewer_notes',
        'uploaded_by_user_id',
        'reviewed_by_user_id',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function applicationDocument(): BelongsTo
    {
        return $this->belongsTo(ApplicationDocument::class, 'application_document_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function typeLabel(): string
    {
        return config(
            "tich-application.document_types.{$this->document_type}",
            str_replace('_', ' ', ucfirst((string) $this->document_type))
        );
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function safeFilename(): string
    {
        $name = preg_replace('/[^\w.\-() ]+/u', '_', $this->original_filename) ?: 'document';

        return $name;
    }
}

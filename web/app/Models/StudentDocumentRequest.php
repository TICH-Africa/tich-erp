<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDocumentRequest extends Model
{
    public const TYPES = [
        'transcript' => 'Certified transcript',
        'recommendation_letter' => 'Recommendation letter',
        'completion_letter' => 'Completion letter',
        'clearance_form' => 'Graduation clearance form',
        'other' => 'Other document',
    ];

    protected $fillable = [
        'student_id',
        'requested_by_user_id',
        'document_type',
        'status',
        'student_notes',
        'reviewer_notes',
        'issued_document_path',
        'reviewed_by_user_id',
        'reviewed_at',
        'issued_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'issued_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->document_type] ?? ucfirst(str_replace('_', ' ', (string) $this->document_type));
    }
}

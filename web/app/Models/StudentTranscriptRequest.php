<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentTranscriptRequest extends Model
{
    protected $fillable = [
        'student_id',
        'requested_by_user_id',
        'status',
        'delivery_method',
        'student_notes',
        'registrar_notes',
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
}

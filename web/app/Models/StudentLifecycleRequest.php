<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentLifecycleRequest extends Model
{
    public const TYPES = [
        'deferment' => 'Academic deferment',
        'withdrawal' => 'Course withdrawal',
        'readmission' => 'Program readmission',
    ];

    protected $fillable = [
        'student_id',
        'requested_by_user_id',
        'request_type',
        'status',
        'effective_date',
        'reason',
        'reviewer_notes',
        'reviewed_by_user_id',
        'reviewed_at',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'reviewed_at' => 'datetime',
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
        return self::TYPES[$this->request_type] ?? ucfirst(str_replace('_', ' ', (string) $this->request_type));
    }
}

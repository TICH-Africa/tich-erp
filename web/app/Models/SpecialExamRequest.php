<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecialExamRequest extends Model
{
    public const STATUSES = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    protected $table = 'special_exam_requests';

    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'exam_result_id',
        'unit_id',
        'semester_id',
        'reason',
        'student_notes',
        'supporting_docs',
        'status',
        'reviewed_by',
        'reviewed_at',
        'reviewed_notes',
        'scheduled_exam_id',
        'created_at',
    ];

    protected $casts = [
        'supporting_docs' => 'array',
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reviewed_by');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }
}

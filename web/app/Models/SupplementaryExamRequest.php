<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplementaryExamRequest extends Model
{
    public const STATUSES = [
        'pending_review' => 'Pending review',
        'pending_fee' => 'Pending fee',
        'on_hold' => 'On hold',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'scheduled' => 'Scheduled',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    public const TYPES = [
        'theory' => 'Theory',
        'clinical' => 'Clinical',
        'both' => 'Theory & clinical',
    ];

    protected $table = 'supplementary_requests';

    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'exam_result_id',
        'unit_id',
        'semester_id',
        'supplementary_type',
        'fee_amount',
        'fee_paid',
        'fee_payment_ref',
        'fee_paid_at',
        'application_status',
        'student_notes',
        'reviewed_by',
        'reviewed_at',
        'reviewed_notes',
        'scheduled_exam_id',
        'new_score',
        'created_at',
    ];

    protected $casts = [
        'fee_amount' => 'decimal:2',
        'fee_paid' => 'boolean',
        'fee_paid_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'new_score' => 'decimal:2',
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
        return self::STATUSES[$this->application_status] ?? ucfirst(str_replace('_', ' ', (string) $this->application_status));
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->supplementary_type] ?? ucfirst((string) $this->supplementary_type);
    }
}

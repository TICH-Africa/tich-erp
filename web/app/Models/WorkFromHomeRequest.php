<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkFromHomeRequest extends Model
{
    public const STATUS_PENDING_HR = 'pending_hr';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_RETURNED = 'returned';

    public const STATUS_CANCELLED = 'cancelled';

    /** Statuses that consume a monthly WFH day entitlement. */
    public const CONSUMING_STATUSES = [
        self::STATUS_PENDING_HR,
        self::STATUS_APPROVED,
    ];

    public const CONSIDERATION_KEYS = [
        'department_open_hours',
        'no_adverse_effect',
        'position_conducive',
        'performance_plan',
    ];

    /** @var array<string, string> */
    public const CONSIDERATION_LABELS = [
        'department_open_hours' => 'The department will continue to be open from 8:00 a.m. until 5:00 p.m., Monday to Friday.',
        'no_adverse_effect' => 'The schedule will not adversely affect the operations of the department.',
        'position_conducive' => 'The position identified for flexible work is conducive to such schedules.',
        'performance_plan' => 'A plan has been developed to monitor the performance of the employee participating in this flexible work arrangement.',
    ];

    protected $fillable = [
        'request_code',
        'staff_id',
        'supervisor_staff_id',
        'supervisor_name',
        'department_name',
        'job_title',
        'arrangement_type',
        'work_date',
        'work_year',
        'work_month',
        'week_of_month',
        'start_time',
        'end_time',
        'total_hours',
        'period_start',
        'period_end',
        'remote_tasks',
        'considerations',
        'status',
        'submitted_at',
        'hr_reviewed_by_staff_id',
        'hr_reviewed_at',
        'hr_notes',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'period_start' => 'date',
            'period_end' => 'date',
            'submitted_at' => 'datetime',
            'hr_reviewed_at' => 'datetime',
            'remote_tasks' => 'array',
            'considerations' => 'array',
            'total_hours' => 'decimal:2',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'supervisor_staff_id');
    }

    public function hrReviewer(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'hr_reviewed_by_staff_id');
    }

    public function isPendingHr(): bool
    {
        return $this->status === self::STATUS_PENDING_HR;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_HR => 'Pending HR',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_RETURNED => 'Returned',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffWeeklyTimeLog extends Model
{
    protected $table = 'staff_weekly_time_logs';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_HR = 'pending_hr';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_RETURNED = 'returned';

    protected $fillable = [
        'log_code',
        'staff_id',
        'log_year',
        'log_month',
        'week_number',
        'week_ref',
        'period_start',
        'period_end',
        'status',
        'total_hours',
        'total_units',
        'employee_signed_name',
        'employee_signed_at',
        'manager_staff_id',
        'manager_signed_name',
        'manager_signature',
        'manager_signed_at',
        'manager_self_endorsed',
        'hr_reviewed_by_staff_id',
        'hr_reviewed_at',
        'hr_notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_hours' => 'decimal:2',
        'total_units' => 'decimal:2',
        'employee_signed_at' => 'datetime',
        'manager_signed_at' => 'datetime',
        'manager_self_endorsed' => 'boolean',
        'hr_reviewed_at' => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'manager_staff_id');
    }

    public function hrReviewer(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'hr_reviewed_by_staff_id');
    }

    public function days(): HasMany
    {
        return $this->hasMany(StaffWeeklyTimeLogDay::class, 'weekly_time_log_id')->orderBy('display_order');
    }

    public function isEditableByEmployee(): bool
    {
        if ($this->manager_signed_at) {
            return false;
        }

        // Editable until line-manager endorsement locks the form (incl. pending_hr).
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_RETURNED,
            self::STATUS_PENDING_HR,
        ], true);
    }

    public function isLocked(): bool
    {
        return (bool) $this->manager_signed_at
            || in_array($this->status, [self::STATUS_APPROVED, self::STATUS_REJECTED], true);
    }

    public function monthLabel(): string
    {
        return \Carbon\Carbon::create($this->log_year, $this->log_month, 1)->format('F Y');
    }
}

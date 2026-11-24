<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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
        if (in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_RETURNED,
        ], true)) {
            return true;
        }

        // A week still being worked stays open even after it has been sent to HR or
        // endorsed, so the days that had not arrived at submit time can still be
        // recorded. Handing in the whole week is what closes the sheet.
        if (in_array($this->status, [
            self::STATUS_PENDING_HR,
            self::STATUS_APPROVED,
        ], true)) {
            return ! $this->isWeekFullyRecorded();
        }

        return false;
    }

    public function canBeSubmittedByEmployee(): bool
    {
        return $this->isEditableByEmployee() && in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_RETURNED,
            self::STATUS_PENDING_HR,
        ], true);
    }

    /**
     * The days of this week the employee answers for: inside the month and not a
     * weekend, whether or not they have been filled in yet.
     *
     * @return Collection<int, StaffWeeklyTimeLogDay>
     */
    public function workingDays(): Collection
    {
        $days = $this->relationLoaded('days') ? $this->days : $this->load('days')->days;

        return $days->filter(
            static fn (StaffWeeklyTimeLogDay $day): bool => $day->in_month
                && $day->work_date !== null
                && ! $day->work_date->isWeekend(),
        );
    }

    /** Every working day carries a time in, a time out and its tasks, so the week is complete. */
    public function isWeekFullyRecorded(): bool
    {
        $working = $this->workingDays();

        return $working->isNotEmpty() && $working->every(
            static fn (StaffWeeklyTimeLogDay $day): bool => filled($day->time_in)
                && filled($day->time_out)
                && filled($day->tasks_accomplished),
        );
    }

    public function isLocked(): bool
    {
        return ! $this->isEditableByEmployee();
    }

    public function monthLabel(): string
    {
        return \Carbon\Carbon::create($this->log_year, $this->log_month, 1)->format('F Y');
    }
}

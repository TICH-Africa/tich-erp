<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffWeeklyTimeLogDay extends Model
{
    protected $table = 'staff_weekly_time_log_days';

    protected $fillable = [
        'weekly_time_log_id',
        'work_date',
        'day_label',
        'in_month',
        'time_in',
        'time_out',
        'tasks_accomplished',
        'initials',
        'department_ids',
        'approval_sign',
        'total_hours',
        'total_units',
        'display_order',
    ];

    protected $casts = [
        'work_date' => 'date',
        'in_month' => 'boolean',
        'department_ids' => 'array',
        'total_hours' => 'decimal:2',
        'total_units' => 'decimal:2',
    ];

    public function weeklyTimeLog(): BelongsTo
    {
        return $this->belongsTo(StaffWeeklyTimeLog::class, 'weekly_time_log_id');
    }
}

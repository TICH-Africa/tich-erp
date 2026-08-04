<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAttendance extends Model
{
    protected $table = 'staff_attendance';

    public $timestamps = false;

    protected $fillable = [
        'staff_id',
        'attendance_date',
        'clock_in_time',
        'clock_out_time',
        'work_hours',
        'is_present',
        'is_leave_day',
        'leave_request_id',
        'is_off_campus',
        'field_project_name',
        'location_lat_long',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'work_hours' => 'decimal:2',
        'is_present' => 'boolean',
        'is_leave_day' => 'boolean',
        'is_off_campus' => 'boolean',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function recordedByStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'recorded_by');
    }
}

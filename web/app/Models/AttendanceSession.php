<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSession extends Model
{
    protected $table = 'attendance_sessions';

    public $timestamps = false;

    protected $fillable = [
        'session_number',
        'unit_allocation_id',
        'session_date',
        'start_time',
        'end_time',
        'venue',
        'session_type',
        'virtual_meeting_url',
        'is_mandatory',
        'total_expected_attendees',
        'signed_sheet_image_path',
        'recorded_by',
        'recorded_at',
        'is_locked',
    ];

    protected $casts = [
        'session_date' => 'date',
        'recorded_at' => 'datetime',
        'is_mandatory' => 'boolean',
        'is_locked' => 'boolean',
    ];

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(UnitAllocation::class, 'unit_allocation_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'session_id');
    }

    public function recordedByStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'recorded_by');
    }
}

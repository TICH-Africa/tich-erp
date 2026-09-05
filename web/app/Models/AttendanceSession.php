<?php

namespace App\Models;

use App\Models\Concerns\PrunesStoredFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSession extends Model
{
    use PrunesStoredFiles;

    protected $table = 'attendance_sessions';

    /** @var array<string, string> */
    protected array $storedFiles = [
        'signed_sheet_image_path' => 'public',
        'class_photo_image_path' => 'public',
    ];

    public $timestamps = false;

    protected $fillable = [
        'session_number',
        'unit_allocation_id',
        'program_timetable_session_id',
        'session_date',
        'start_time',
        'end_time',
        'venue',
        'session_type',
        'virtual_meeting_url',
        'is_mandatory',
        'total_expected_attendees',
        'signed_sheet_image_path',
        'sheet_image_hash',
        'class_photo_image_path',
        'class_photo_image_hash',
        'recorded_by',
        'recorded_at',
        'is_locked',
        'verification_status',
        'submitted_at',
        'hod_verified_by',
        'hod_verified_at',
        'registrar_verified_by',
        'registrar_verified_at',
        'roster_verified_by',
        'roster_verified_at',
        'exam_eligibility_checked_by',
        'exam_eligibility_checked_at',
    ];

    protected $casts = [
        'session_date' => 'date',
        'recorded_at' => 'datetime',
        'submitted_at' => 'datetime',
        'hod_verified_at' => 'datetime',
        'registrar_verified_at' => 'datetime',
        'roster_verified_at' => 'datetime',
        'exam_eligibility_checked_at' => 'datetime',
        'is_mandatory' => 'boolean',
        'is_locked' => 'boolean',
    ];

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(UnitAllocation::class, 'unit_allocation_id');
    }

    public function timetableSession(): BelongsTo
    {
        return $this->belongsTo(ProgramTimetableSession::class, 'program_timetable_session_id');
    }

    public function isFromTimetable(): bool
    {
        return $this->program_timetable_session_id !== null;
    }

    public function records(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'session_id');
    }

    public function recordedByStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'recorded_by');
    }

    public function hodVerifier(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'hod_verified_by');
    }

    public function registrarVerifier(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'registrar_verified_by');
    }

    public function rosterVerifier(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'roster_verified_by');
    }
}

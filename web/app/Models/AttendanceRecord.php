<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    protected $table = 'attendance_records';

    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'student_id',
        'is_present',
        'sign_in_time',
        'recorded_by_tutor',
        'verified_by_hod',
        'verification_note',
    ];

    protected $casts = [
        'is_present' => 'boolean',
        'recorded_by_tutor' => 'boolean',
        'verified_by_hod' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class, 'session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}

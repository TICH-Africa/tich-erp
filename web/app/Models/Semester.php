<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Semester extends Model
{
    protected $table = 'semesters';

    public $timestamps = false;

    protected $fillable = [
        'academic_year_id', 'semester_label', 'semester_number', 'intake_month',
        'start_date', 'end_date', 'registration_open_date', 'registration_close_date',
        'is_current', 'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'registration_open_date' => 'date',
        'registration_close_date' => 'date',
        'is_current' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }
}

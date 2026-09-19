<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicRecord extends Model
{
    protected $table = 'academic_records';

    protected $fillable = [
        'student_id',
        'program_id',
        'academic_year_id',
        'semester_id',
        'enrollment_date',
        'completion_date',
        'status',
        'gpa',
        'units_registered',
        'units_completed',
        'entry_pathway',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'completion_date' => 'date',
        'gpa' => 'decimal:2',
        'units_registered' => 'integer',
        'units_completed' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(AcademicProgram::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId)->orderByDesc('enrollment_date');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForProgram($query, int $programId)
    {
        return $query->where('program_id', $programId);
    }
}

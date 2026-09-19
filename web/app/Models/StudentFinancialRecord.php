<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentFinancialRecord extends Model
{
    protected $table = 'student_financial_records';

    protected $fillable = [
        'student_id',
        'academic_year_id',
        'semester_id',
        'total_chargeable',
        'total_paid',
        'outstanding_balance',
        'payment_method',
        'payment_reference',
        'payment_date',
        'recorded_by',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'total_chargeable' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'payment_date' => 'date',
        'recorded_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'recorded_by');
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId)->orderByDesc('payment_date');
    }
}

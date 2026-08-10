<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeStructure extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'program_id',
        'academic_year_id',
        'semester_number',
        'tuition_fee',
        'examination_fee',
        'library_fee',
        'activity_fee',
        'hostel_fee',
        'medical_insurance_fee',
        'nursing_clinical_fee',
        'graduation_fee',
        'registration_fee',
        'other_fees',
        'total_semester_fee',
        'is_approved',
        'approved_by',
        'approved_at',
        'effective_from',
        'is_active',
    ];

    protected $casts = [
        'tuition_fee' => 'decimal:2',
        'examination_fee' => 'decimal:2',
        'library_fee' => 'decimal:2',
        'activity_fee' => 'decimal:2',
        'hostel_fee' => 'decimal:2',
        'medical_insurance_fee' => 'decimal:2',
        'nursing_clinical_fee' => 'decimal:2',
        'graduation_fee' => 'decimal:2',
        'registration_fee' => 'decimal:2',
        'other_fees' => 'array',
        'total_semester_fee' => 'decimal:2',
        'is_approved' => 'boolean',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'approved_at' => 'datetime',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(AcademicProgram::class, 'program_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by');
    }

    public function recalculateTotal(): void
    {
        $otherTotal = collect($this->other_fees ?? [])->sum(fn ($fee) => (float) ($fee['amount'] ?? 0));

        $this->total_semester_fee = round(
            (float) $this->tuition_fee
            + (float) $this->examination_fee
            + (float) $this->library_fee
            + (float) $this->activity_fee
            + (float) $this->hostel_fee
            + (float) $this->medical_insurance_fee
            + (float) $this->nursing_clinical_fee
            + (float) $this->graduation_fee
            + (float) $this->registration_fee
            + $otherTotal,
            2
        );
    }
}

<?php

namespace App\Models\Finance;

use App\Models\AcademicProgram;
use App\Models\AcademicYear;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeStructure extends Model
{
    protected $table = 'fee_structures';
    public $timestamps = false;

    protected $fillable = [
        'program_id',
        'academic_year_id',
        'application_fee',
        'tuition_fee',
        'cautions_fee',
        'computer_lab_fee',
        'accommodation_fee',
        'transport_fee',
        'partnership_fee',
        'id_card_fee',
        'student_union_fee',
        'quality_assurance_fee',
        'emergency_fund_fee',
        'library_fee',
        'indexing_nck_fee',
        'examination_external_fee',
        'attachment_fee',
        'graduation_fee',
        'other_fees',
        'total_semester_fee',
        'is_approved',
        'approved_by',
        'approved_at',
        'effective_from',
        'is_active',
    ];

    protected $casts = [
        'other_fees' => 'array',
        'is_approved' => 'boolean',
        'is_active' => 'boolean',
        'approved_at' => 'datetime',
        'effective_from' => 'date',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(AcademicProgram::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}

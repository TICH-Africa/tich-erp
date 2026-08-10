<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeStructure extends Model
{
    public $timestamps = false;

    /** @var array<string, string> */
    public const SEMESTER_CHARGES = [
        'tuition_fee' => 'Tuition fee',
        'caution_fee' => 'Caution fee',
        'computer_lab_fee' => 'Computer lab fees',
        'partnership_fee' => 'Partnership',
        'id_card_fee' => 'ID card',
        'student_union_fee' => 'Student union',
        'emergency_fund_fee' => 'Emergency fund',
        'library_fee' => 'Library',
        'examination_external_fee' => 'Examination (external)',
        'attachment_fee' => 'Attachment fee',
    ];

    /** @var array<string, string> */
    public const OPTIONAL_SEMESTER_CHARGES = [
        'transport_fee' => 'Transport (per booklet)',
        'accommodation_fee' => 'Accommodation',
    ];

    protected $fillable = [
        'program_id',
        'academic_year_id',
        'application_fee',
        'tuition_fee',
        'caution_fee',
        'computer_lab_fee',
        'transport_fee',
        'transport_optional',
        'accommodation_fee',
        'accommodation_optional',
        'partnership_fee',
        'id_card_fee',
        'student_union_fee',
        'emergency_fund_fee',
        'library_fee',
        'examination_external_fee',
        'attachment_fee',
        'qa_annual_fee',
        'indexing_nck_fee',
        'requires_indexing_nck',
        'graduation_fee',
        'total_semester_fee',
        'is_approved',
        'approved_by',
        'approved_at',
        'effective_from',
        'is_active',
    ];

    protected $casts = [
        'application_fee' => 'decimal:2',
        'tuition_fee' => 'decimal:2',
        'caution_fee' => 'decimal:2',
        'computer_lab_fee' => 'decimal:2',
        'transport_fee' => 'decimal:2',
        'transport_optional' => 'boolean',
        'accommodation_fee' => 'decimal:2',
        'accommodation_optional' => 'boolean',
        'partnership_fee' => 'decimal:2',
        'id_card_fee' => 'decimal:2',
        'student_union_fee' => 'decimal:2',
        'emergency_fund_fee' => 'decimal:2',
        'library_fee' => 'decimal:2',
        'examination_external_fee' => 'decimal:2',
        'attachment_fee' => 'decimal:2',
        'qa_annual_fee' => 'decimal:2',
        'indexing_nck_fee' => 'decimal:2',
        'requires_indexing_nck' => 'boolean',
        'graduation_fee' => 'decimal:2',
        'total_semester_fee' => 'decimal:2',
        'is_approved' => 'boolean',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (FeeStructure $feeStructure) {
            $feeStructure->applyDefaults();
        });
    }

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

    public function applyDefaults(): void
    {
        $defaults = config('finance.fee_defaults', []);

        $this->application_fee ??= $defaults['application_fee'] ?? 1000;
        $this->qa_annual_fee ??= $defaults['qa_annual_fee'] ?? 1000;
        $this->graduation_fee ??= $defaults['graduation_fee'] ?? 4000;
        $this->transport_optional ??= true;
        $this->accommodation_optional ??= true;
    }

    public function recalculateTotal(): void
    {
        $total = 0.0;

        foreach (array_keys(self::SEMESTER_CHARGES) as $field) {
            $total += (float) ($this->{$field} ?? 0);
        }

        $this->total_semester_fee = round($total, 2);
    }

    /**
     * @return list<string>
     */
    public function semesterChargeLines(bool $includeOptional = false): array
    {
        $lines = [];

        foreach (self::SEMESTER_CHARGES as $field => $label) {
            $amount = (float) ($this->{$field} ?? 0);
            if ($amount > 0) {
                $lines[] = "{$label}: KES ".number_format($amount, 2);
            }
        }

        if ($includeOptional) {
            foreach (self::OPTIONAL_SEMESTER_CHARGES as $field => $label) {
                $amount = (float) ($this->{$field} ?? 0);
                if ($amount > 0) {
                    $suffix = $field === 'transport_fee' ? ' (optional, per booklet)' : ' (optional)';
                    $lines[] = "{$label}{$suffix}: KES ".number_format($amount, 2);
                }
            }
        }

        return $lines;
    }

    public function optionalSemesterTotal(): float
    {
        return round((float) $this->transport_fee + (float) $this->accommodation_fee, 2);
    }
}

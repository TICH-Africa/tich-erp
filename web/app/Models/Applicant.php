<?php

namespace App\Models;

use App\Models\CurriculumVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Applicant extends Model
{
    protected $table = 'applicants';

    public $timestamps = false;

    protected $fillable = [
        'application_number',
        'program_id',
        'intake_year',
        'intake_month',
        'handling_department_id',
        'preferred_campus_id',
        'first_name',
        'middle_name',
        'surname',
        'date_of_birth',
        'gender',
        'nationality',
        'national_id_number',
        'passport_number',
        'email',
        'phone_number',
        'home_county',
        'postal_address',
        'entry_qualification',
        'kcse_grade',
        'kcse_year',
        'previous_institution',
        'sponsorship_type',
        'sponsor_organization',
        'sponsor_address',
        'sponsor_phone',
        'next_of_kin_name',
        'next_of_kin_relationship',
        'next_of_kin_address',
        'next_of_kin_phone',
        'application_fee_paid',
        'application_fee_paid_at',
        'application_fee_payment_ref',
        'status',
        'academic_review_status',
        'review_notes',
        'rejection_reason',
        'reviewed_at',
        'academic_reviewer_id',
        'application_source',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'application_fee_paid' => 'boolean',
        'application_fee_paid_at' => 'datetime',
        'created_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(AcademicProgram::class, 'program_id');
    }

    public function handlingDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'handling_department_id');
    }

    public function preferredCampus(): BelongsTo
    {
        return $this->belongsTo(Campus::class, 'preferred_campus_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class, 'applicant_id');
    }

    public function student(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Student::class, 'application_id');
    }

    public function fullName(): string
    {
        return trim(collect([$this->first_name, $this->middle_name, $this->surname])->filter()->implode(' '));
    }

    public function intakeLabel(): string
    {
        if ($this->intake_year && $this->intake_month) {
            $month = CurriculumVersion::intakeMonths()[(int) $this->intake_month] ?? (string) $this->intake_month;

            return "{$month} {$this->intake_year}";
        }

        return 'Not specified';
    }

    public function isPendingReview(): bool
    {
        return in_array($this->status, ['submitted_admin', 'submitted', 'academic_review'], true)
            && ! in_array($this->academic_review_status, ['approved', 'rejected'], true);
    }

    public function isFinalized(): bool
    {
        return in_array($this->status, ['admitted', 'rejected'], true);
    }

    public function canPayApplicationFee(): bool
    {
        if ($this->application_fee_paid || $this->isFinalized()) {
            return false;
        }

        return $this->status === 'fee_pending'
            && in_array($this->academic_review_status, ['approved', 'shortlisted'], true);
    }
}

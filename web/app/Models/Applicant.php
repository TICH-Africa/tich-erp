<?php

namespace App\Models;

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
        'national_id_number',
        'passport_number',
        'email',
        'phone_number',
        'home_county',
        'entry_qualification',
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

    public function isPendingReview(): bool
    {
        return in_array($this->status, ['submitted', 'academic_review'], true)
            && ! in_array($this->academic_review_status, ['approved', 'rejected'], true);
    }

    public function isFinalized(): bool
    {
        return in_array($this->status, ['admitted', 'rejected'], true);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Staff extends Model
{
    use HasFactory;
    protected $table = 'staff';

    protected $fillable = [
        'employee_number',
        'title',
        'first_name',
        'middle_name',
        'surname',
        'date_of_birth',
        'gender',
        'marital_status',
        'national_id_number',
        'passport_number',
        'nationality',
        'home_county',
        'email',
        'phone_number',
        'alt_phone_number',
        'postal_address',
        'postal_code',
        'physical_address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'photo_path',
        'department_id',
        'campus_id',
        'job_title',
        'job_grade',
        'employment_category',
        'employment_start_date',
        'contract_end_date',
        'is_on_probation',
        'probation_end_date',
        'confirmation_date',
        'gross_monthly_salary',
        'allowances_json',
        'bank_id',
        'kra_pin',
        'nssf_number',
        'sha_number',
        'helb_number',
        'pension_scheme_id',
        'employment_status',
        'exit_date',
        'exit_reason',
        'user_id',
        'is_teaching_staff',
        'is_nursing_license_required',
        'line_manager_id',
        'salary_scale',
        'incremental_date',
        'project_code',
        'is_profile_locked',
        'onboarding_completed_at',
    ];

    protected $casts = [
        'is_teaching_staff' => 'boolean',
        'is_nursing_license_required' => 'boolean',
        'is_on_probation' => 'boolean',
        'is_profile_locked' => 'boolean',
        'allowances_json' => 'array',
        'date_of_birth' => 'date',
        'employment_start_date' => 'date',
        'contract_end_date' => 'date',
        'probation_end_date' => 'date',
        'confirmation_date' => 'date',
        'incremental_date' => 'date',
        'exit_date' => 'date',
        'onboarding_completed_at' => 'datetime',
        'gross_monthly_salary' => 'decimal:2',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lineManager(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'line_manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Staff::class, 'line_manager_id');
    }

    public function unitAllocations(): HasMany
    {
        return $this->hasMany(UnitAllocation::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(StaffBankAccount::class, 'bank_id');
    }

    public function pensionScheme(): BelongsTo
    {
        return $this->belongsTo(PensionScheme::class);
    }

    public function nextOfKin(): HasMany
    {
        return $this->hasMany(StaffNextOfKin::class);
    }

    public function primaryNextOfKin(): HasOne
    {
        return $this->hasOne(StaffNextOfKin::class)->where('is_primary', 1);
    }

    public function allowances(): HasMany
    {
        return $this->hasMany(StaffAllowance::class);
    }

    public function activeAllowances(): HasMany
    {
        return $this->hasMany(StaffAllowance::class)->where('is_active', 1);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StaffDocument::class);
    }

    public function onboarding(): HasMany
    {
        return $this->hasMany(StaffOnboarding::class);
    }

    public function latestOnboarding(): HasOne
    {
        return $this->hasOne(StaffOnboarding::class)->latestOfMany();
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(StaffStatusHistory::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(StaffContract::class);
    }

    public function qualifications(): HasMany
    {
        return $this->hasMany(StaffQualification::class);
    }

    public function professionalLicenses(): HasMany
    {
        return $this->hasMany(StaffProfessionalLicense::class);
    }

    public function disciplinaryCases(): HasMany
    {
        return $this->hasMany(StaffDisciplinaryCase::class);
    }

    public function professionalDevelopment(): HasMany
    {
        return $this->hasMany(ProfessionalDevelopment::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(StaffAttendance::class);
    }

    public function performanceReviews(): HasMany
    {
        return $this->hasMany(PerformanceReview::class);
    }

    public function fullName(): string
    {
        return trim(implode(' ', array_filter([$this->title, $this->first_name, $this->middle_name, $this->surname])));
    }

    public function getTotalMonthlyCompensationAttribute(): float
    {
        $allowances = $this->activeAllowances()->sum('amount');

        return (float) $this->gross_monthly_salary + $allowances;
    }

    public function isOnProbation(): bool
    {
        return (bool) $this->is_on_probation;
    }

    public function isActive(): bool
    {
        return $this->employment_status === 'active';
    }

    public function isOnLeave(): bool
    {
        return $this->employment_status === 'on_leave';
    }

    public function isSuspended(): bool
    {
        return $this->employment_status === 'suspended';
    }

    public function isTerminated(): bool
    {
        return $this->employment_status === 'terminated';
    }

    public function hasResigned(): bool
    {
        return $this->employment_status === 'resigned';
    }

    public function hasExited(): bool
    {
        return in_array($this->employment_status, ['terminated', 'resigned', 'retired', 'deceased'], true);
    }

    public function scopeActive($query)
    {
        return $query->where('employment_status', 'active');
    }

    public function scopeOnboarding($query)
    {
        return $query->where('employment_status', 'onboarding');
    }
}

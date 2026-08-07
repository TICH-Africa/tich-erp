<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $table = 'leave_types';

    public $timestamps = false;

    protected $fillable = [
        'leave_code',
        'leave_name',
        'days_allowed_per_year',
        'accrual_type',
        'accrual_rate',
        'calculation_type',
        'is_paid',
        'requires_medical_certificate',
        'requires_certificate',
        'requires_hod_approval',
        'requires_hr_approval',
        'gender_restriction',
        'min_service_months',
        'carry_forward_days',
        'max_consecutive_days',
        'notice_period_days',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_payable' => 'boolean',
        'is_paid' => 'boolean',
        'requires_medical_certificate' => 'boolean',
        'requires_certificate' => 'boolean',
        'requires_hod_approval' => 'boolean',
        'requires_hr_approval' => 'boolean',
        'is_active' => 'boolean',
        'days_allowed_per_year' => 'integer',
        'accrual_rate' => 'decimal:2',
        'min_service_months' => 'integer',
        'carry_forward_days' => 'integer',
        'max_consecutive_days' => 'integer',
        'notice_period_days' => 'integer',
    ];

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}

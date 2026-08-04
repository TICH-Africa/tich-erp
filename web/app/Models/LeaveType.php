<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $table = 'leave_types';

    public $timestamps = false;

    protected $fillable = [
        'leave_code',
        'leave_name',
        'days_allowed_per_year',
        'is_payable',
        'requires_medical_certificate',
        'requires_approval_hod',
        'requires_approval_hr',
        'gender_restriction',
        'min_service_months',
        'carry_forward_days',
        'is_active',
    ];

    protected $casts = [
        'is_payable' => 'boolean',
        'requires_medical_certificate' => 'boolean',
        'requires_approval_hod' => 'boolean',
        'requires_approval_hr' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}

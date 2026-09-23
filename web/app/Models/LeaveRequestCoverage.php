<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveRequestCoverage extends Model
{
    protected $table = 'leave_request_coverages';

    protected $fillable = [
        'leave_request_id',
        'department_id',
        'cover_staff_id',
        'status',
        'notified_at',
        'access_granted_at',
        'access_revoked_at',
    ];

    protected $casts = [
        'notified_at' => 'datetime',
        'access_granted_at' => 'datetime',
        'access_revoked_at' => 'datetime',
    ];

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function coverStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'cover_staff_id');
    }

    public function accessGrants(): HasMany
    {
        return $this->hasMany(LeaveCoverageAccessGrant::class, 'leave_request_coverage_id');
    }
}

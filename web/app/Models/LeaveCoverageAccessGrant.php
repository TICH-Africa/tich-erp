<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveCoverageAccessGrant extends Model
{
    protected $table = 'leave_coverage_access_grants';

    protected $fillable = [
        'leave_request_coverage_id',
        'cover_user_id',
        'role_id',
        'department_id',
        'campus_id',
        'was_preexisting',
        'granted_at',
        'revoked_at',
    ];

    protected $casts = [
        'was_preexisting' => 'boolean',
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function coverage(): BelongsTo
    {
        return $this->belongsTo(LeaveRequestCoverage::class, 'leave_request_coverage_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveRequestCoverage extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_DECLINED = 'declined';

    protected $table = 'leave_request_coverages';

    protected $fillable = [
        'leave_request_id',
        'department_id',
        'cover_staff_id',
        'status',
        'notified_at',
        'responded_at',
        'response_notes',
        'access_granted_at',
        'access_revoked_at',
    ];

    protected $casts = [
        'notified_at' => 'datetime',
        'responded_at' => 'datetime',
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

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isDeclined(): bool
    {
        return $this->status === self::STATUS_DECLINED;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Awaiting response',
            self::STATUS_ACCEPTED => 'Accepted',
            self::STATUS_DECLINED => 'Declined',
            default => ucfirst((string) $this->status),
        };
    }
}

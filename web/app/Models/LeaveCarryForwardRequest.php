<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveCarryForwardRequest extends Model
{
    protected $table = 'leave_carry_forward_requests';

    protected $fillable = [
        'staff_id',
        'leave_type_id',
        'from_year',
        'to_year',
        'days_requested',
        'days_approved',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'days_requested' => 'decimal:2',
        'days_approved' => 'decimal:2',
        'from_year' => 'integer',
        'to_year' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reviewed_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Awaiting HR approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => ucfirst($this->status),
        };
    }
}

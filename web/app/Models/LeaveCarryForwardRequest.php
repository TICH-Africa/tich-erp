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
        'line_manager_status',
        'line_manager_staff_id',
        'line_manager_acted_at',
        'line_manager_notes',
        'hr_status',
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
        'line_manager_acted_at' => 'datetime',
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

    public function lineManager(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'line_manager_staff_id');
    }

    public function statusLabel(): string
    {
        if ($this->status === 'approved') {
            return 'Approved';
        }
        if ($this->status === 'rejected') {
            return 'Rejected';
        }

        $lm = $this->line_manager_status ?? 'pending';
        if ($lm === 'pending') {
            return 'Awaiting line manager';
        }
        if ($lm === 'approved' && ($this->hr_status ?? 'pending') === 'pending') {
            return 'Awaiting HR approval';
        }

        return match ($this->status) {
            'pending' => 'Pending',
            default => ucfirst((string) $this->status),
        };
    }

    public function awaitingHr(): bool
    {
        return $this->status === 'pending'
            && ($this->line_manager_status ?? 'pending') === 'approved'
            && ($this->hr_status ?? 'pending') === 'pending';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    protected $table = 'leave_requests';

    public $timestamps = true;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'leave_number',
        'staff_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'days_requested',
        'reason',
        'is_emergency',
        'medical_certificate_path',
        'hod_approval_status',
        'hod_approved_by',
        'hod_approved_at',
        'hr_approval_status',
        'hr_approved_by',
        'hr_approved_at',
        'overall_status',
        'is_cancelled',
        'cancellation_reason',
        'return_date',
        'is_completed',
        'handover_notes',
        'hr_review_notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'return_date' => 'date',
        'hod_approved_at' => 'datetime',
        'hr_approved_at' => 'datetime',
        'days_requested' => 'integer',
        'is_emergency' => 'boolean',
        'is_cancelled' => 'boolean',
        'is_completed' => 'boolean',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function hodApprovedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'hod_approved_by');
    }

    public function hrApprovedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'hr_approved_by');
    }

    public function isEditableByEmployee(): bool
    {
        return $this->overall_status === 'returned' && ! $this->is_cancelled;
    }

    public function isCancellableByEmployee(): bool
    {
        return in_array($this->overall_status, ['pending_hr', 'returned'], true) && ! $this->is_cancelled;
    }

    public function statusLabel(): string
    {
        return match ($this->overall_status) {
            'pending_hr' => 'Awaiting HR review',
            'returned' => 'Returned for changes',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', $this->overall_status)),
        };
    }
}

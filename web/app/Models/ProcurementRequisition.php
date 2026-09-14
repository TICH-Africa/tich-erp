<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementRequisition extends Model
{
    protected $table = 'procurement_requisitions';

    protected $fillable = [
        'requisition_number',
        'requesting_department_id',
        'requested_by',
        'request_date',
        'justification',
        'estimated_cost',
        'budget_code',
        'budget_line',
        'delivery_required_by',
        'delivery_location',
        'status',
        'hod_approval_status',
        'hod_approved_by',
        'hod_approved_at',
        'finance_approval_status',
        'finance_approved_by',
        'finance_approved_at',
        'ceo_approval_status',
        'ceo_approved_by',
        'ceo_approved_at',
        'requested_item',
        'attachments',
        'estimated_unit_cost',
        'quantity',
        'audit_trail',
    ];

    protected $casts = [
        'request_date' => 'date',
        'delivery_required_by' => 'date',
        'estimated_cost' => 'decimal:2',
        'estimated_unit_cost' => 'decimal:2',
        'quantity' => 'decimal:2',
        'hod_approved_at' => 'datetime',
        'finance_approved_at' => 'datetime',
        'ceo_approved_at' => 'datetime',
        'attachments' => 'array',
        'audit_trail' => 'array',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'requesting_department_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'requested_by');
    }

    public function hodApprover(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'hod_approved_by');
    }

    public function financeApprover(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'finance_approved_by');
    }

    public function ceoApprover(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'ceo_approved_by');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopePendingHodApproval($query)
    {
        return $query->where('status', 'submitted')->where('hod_approval_status', 'pending');
    }

    public function scopePendingFinanceApproval($query)
    {
        return $query->where('status', 'hod_approved')->where('finance_approval_status', 'pending');
    }

    public function scopePendingCeoApproval($query)
    {
        return $query->where('status', 'finance_approved')->where('ceo_approval_status', 'pending');
    }

    public function scopePendingBoardApproval($query)
    {
        return $query->where('status', 'ceo_approved');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isHodApproved(): bool
    {
        return $this->status === 'hod_approved';
    }

    public function isFinanceApproved(): bool
    {
        return $this->status === 'finance_approved';
    }

    public function isCeoApproved(): bool
    {
        return $this->status === 'ceo_approved';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isRejected(): bool
    {
        return in_array($this->status, ['rejected', 'cancelled']);
    }

    public function statusLabel(): string
    {
        $labels = [
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'hod_approved' => 'HOD Approved',
            'finance_approved' => 'Finance Approved',
            'ceo_approved' => 'CEO Approved',
            'completed' => 'Completed',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
        ];

        return $labels[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function currentApprovalLevel(): string
    {
        if ($this->status === 'draft') {
            return 'draft';
        }

        if ($this->status === 'submitted') {
            return 'hod';
        }

        if ($this->status === 'hod_approved') {
            return 'finance';
        }

        if ($this->status === 'finance_approved') {
            return 'ceo';
        }

        if ($this->status === 'ceo_approved' || $this->status === 'completed') {
            return 'board';
        }

        return 'completed';
    }
}

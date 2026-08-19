<?php

namespace App\Models\Administration;

use App\Models\Department;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetRequest extends Model
{
    protected $table = 'admin_budget_requests';

    protected $fillable = [
        'request_code', 'planning_cycle_id', 'department_id', 'title', 'framework', 'budget_type',
        'standard_line_items', 'cbe_details',
        'requested_amount', 'verified_amount', 'approved_amount', 'allocated_amount', 'status',
        'justification', 'submitted_by', 'submitted_at',
        'is_late', 'deadline_at',
        'finance_verified_by', 'finance_verified_at',
        'executive_approved_by', 'executive_approved_at',
        'disbursed_by', 'disbursed_at', 'receipt_number', 'workflow_notes',
        'group_allocations',
    ];

    protected $casts = [
        'standard_line_items' => 'array',
        'cbe_details' => 'array',
        'group_allocations' => 'array',
        'requested_amount' => 'decimal:2',
        'verified_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'allocated_amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'is_late' => 'boolean',
        'deadline_at' => 'datetime',
        'finance_verified_at' => 'datetime',
        'executive_approved_at' => 'datetime',
        'disbursed_at' => 'datetime',
    ];

    public function planningCycle(): BelongsTo
    {
        return $this->belongsTo(PlanningCycle::class, 'planning_cycle_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(FundAllocation::class, 'budget_request_id');
    }
}

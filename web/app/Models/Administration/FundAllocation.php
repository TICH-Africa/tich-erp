<?php

namespace App\Models\Administration;

use App\Models\Department;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FundAllocation extends Model
{
    protected $table = 'admin_fund_allocations';

    protected $fillable = [
        'allocation_code', 'budget_request_id', 'department_id',
        'fiscal_year', 'month', 'amount', 'status',
        'released_by', 'released_at', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'released_at' => 'datetime',
    ];

    public function budgetRequest(): BelongsTo
    {
        return $this->belongsTo(BudgetRequest::class, 'budget_request_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}

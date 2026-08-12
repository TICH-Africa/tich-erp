<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceBudget extends Model
{
    protected $table = 'finance_budgets';

    protected $fillable = [
        'budget_code',
        'budget_name',
        'budget_type',
        'department_id',
        'fiscal_year',
        'period_start',
        'period_end',
        'allocated_amount',
        'spent_amount',
        'committed_amount',
        'status',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'allocated_amount' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'committed_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by');
    }

    public function availableAmount(): float
    {
        return max(
            round((float) $this->allocated_amount - (float) $this->spent_amount - (float) $this->committed_amount, 2),
            0
        );
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceBudgetCycle extends Model
{
    protected $table = 'finance_budget_cycles';

    protected $fillable = [
        'budget_id',
        'cycle_type',
        'label',
        'period_start',
        'period_end',
        'allocated_amount',
        'spent_amount',
        'committed_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'allocated_amount' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'committed_amount' => 'decimal:2',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(FinanceBudget::class, 'budget_id');
    }

    public function availableAmount(): float
    {
        return max(
            round((float) $this->allocated_amount - (float) $this->spent_amount - (float) $this->committed_amount, 2),
            0
        );
    }
}

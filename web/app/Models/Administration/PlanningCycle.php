<?php

namespace App\Models\Administration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanningCycle extends Model
{
    protected $table = 'admin_planning_cycles';

    protected $fillable = [
        'cycle_code', 'title', 'plan_tier', 'fiscal_year',
        'period_start', 'period_end', 'requisition_deadline',
        'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'requisition_deadline' => 'datetime',
    ];

    public function budgetRequests(): HasMany
    {
        return $this->hasMany(BudgetRequest::class, 'planning_cycle_id');
    }

    public function isPastDeadline(): bool
    {
        return $this->requisition_deadline !== null && $this->requisition_deadline->isPast();
    }
}

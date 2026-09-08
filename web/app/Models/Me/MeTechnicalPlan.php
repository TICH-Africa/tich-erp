<?php

namespace App\Models\Me;

use App\Models\Administration\BudgetRequest;
use App\Models\Administration\PlanningCycle;
use App\Models\Department;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeTechnicalPlan extends Model
{
    protected $table = 'me_technical_plans';

    protected $fillable = [
        'budget_request_id',
        'planning_cycle_id',
        'department_id',
        'title',
        'fiscal_year',
        'status',
        'summary',
        'submitted_by',
        'submitted_at',
        'me_reviewed_by',
        'me_reviewed_at',
        'me_notes',
        'baseline_locked_by',
        'baseline_locked_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'me_reviewed_at' => 'datetime',
        'baseline_locked_at' => 'datetime',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function budgetRequest(): BelongsTo
    {
        return $this->belongsTo(BudgetRequest::class, 'budget_request_id');
    }

    public function planningCycle(): BelongsTo
    {
        return $this->belongsTo(PlanningCycle::class, 'planning_cycle_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function meReviewer(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'me_reviewed_by');
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(MePlanOutput::class, 'technical_plan_id')->orderBy('display_order');
    }

    public function quarters(): HasMany
    {
        return $this->hasMany(MeQuarter::class, 'technical_plan_id')->orderBy('quarter_number');
    }

    public function quarterlyReports(): HasMany
    {
        return $this->hasMany(MeQuarterlyReport::class, 'technical_plan_id');
    }

    public function isBaselineLocked(): bool
    {
        return $this->status === 'baseline_locked';
    }
}

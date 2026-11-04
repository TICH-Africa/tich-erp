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

    /**
     * Budget-derived plans carry no quarter tags and keep the old whole-plan lock;
     * plans filed on the quarterly grid are approved one quarter at a time.
     */
    public function usesQuarterTags(): bool
    {
        $this->loadMissing('outputs');

        return $this->outputs->contains(fn (MePlanOutput $output) => $output->quarter !== null);
    }

    /**
     * @return array<int, MeQuarter> keyed by quarter number
     */
    public function quartersByNumber(): array
    {
        $this->loadMissing('quarters');

        return $this->quarters->keyBy('quarter_number')->all();
    }

    public function quarterState(int $quarterNumber): string
    {
        $row = $this->quartersByNumber()[$quarterNumber] ?? null;

        return $row?->status ?? 'open';
    }

    public function isQuarterApproved(int $quarterNumber): bool
    {
        return $this->quarterState($quarterNumber) === 'me_approved';
    }

    /**
     * M&E has cleared a quarter, so its outputs are baseline and the department
     * may no longer type over them.
     */
    public function isQuarterEditable(int $quarterNumber): bool
    {
        if ($this->isBaselineLocked()) {
            return false;
        }

        // A budget-derived plan carries no quarter tags, so M&E clears it as one document.
        return ! $this->usesQuarterTags() || ! $this->isQuarterApproved($quarterNumber);
    }

    /**
     * @return list<int>
     */
    public function editableQuarterNumbers(): array
    {
        return array_values(array_filter(
            [1, 2, 3, 4],
            fn (int $q) => $this->isQuarterEditable($q)
        ));
    }

    /**
     * The department can already report achievement for a quarter M&E has cleared,
     * without waiting for the rest of the year.
     */
    public function isQuarterReportable(int $quarterNumber): bool
    {
        return $this->isBaselineLocked() || $this->isQuarterApproved($quarterNumber);
    }

    /**
     * The baseline can only be stamped once every quarter of the year is filled in
     * and cleared by M&E.
     */
    public function allQuartersApproved(): bool
    {
        $this->loadMissing(['outputs', 'quarters']);

        foreach ([1, 2, 3, 4] as $q) {
            $row = $this->quarters->firstWhere('quarter_number', $q);
            $filled = $this->outputs->contains(fn (MePlanOutput $o) => (int) $o->quarter === $q);

            if (! $row || ! $filled || $row->status !== 'me_approved') {
                return false;
            }
        }

        return true;
    }
}

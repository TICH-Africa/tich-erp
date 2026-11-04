<?php

namespace App\Services\Me;

use App\Models\Administration\BudgetRequest;
use App\Models\Department;
use App\Models\Me\MePlanOutput;
use App\Models\Me\MeQuarter;
use App\Models\Me\MeQuarterlyReport;
use App\Models\Me\MeQuarterlyReportLine;
use App\Models\Me\MeTechnicalPlan;
use App\Models\Staff;
use App\Models\User;
use App\Services\PlatformNotificationService;
use App\Services\StaffPortalService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MeTechnicalPlanService
{
    public function __construct(
        protected StaffPortalService $staffPortal,
        protected PlatformNotificationService $notifications,
    ) {}

    /**
     * Dual-ingestion: create/update technical plan when a budget request is submitted.
     *
     * @param  list<array{output: string, activity: string, costable_item?: ?string, planned: float|int|string, planned_unit?: ?string, quarter?: int|null}>  $outputs
     */
    public function ingestFromBudgetSubmission(
        BudgetRequest $budgetRequest,
        Department $department,
        User $user,
        array $outputs,
        ?string $summary = null,
    ): MeTechnicalPlan {
        return DB::transaction(function () use ($budgetRequest, $department, $user, $outputs, $summary) {
            $plan = MeTechnicalPlan::query()
                ->where('budget_request_id', $budgetRequest->id)
                ->first();

            $fiscalYear = null;
            if ($budgetRequest->planning_cycle_id) {
                $budgetRequest->loadMissing('planningCycle');
                $fiscalYear = $budgetRequest->planningCycle?->fiscal_year
                    ?? $budgetRequest->planningCycle?->cycle_code;
            }

            $attrs = [
                'planning_cycle_id' => $budgetRequest->planning_cycle_id,
                'department_id' => $department->id,
                'title' => $budgetRequest->title.' - Technical plan',
                'fiscal_year' => $fiscalYear,
                // Held until Administration forwards to Finance & M&E.
                'status' => 'draft',
                'summary' => $summary,
                'submitted_by' => $user->id,
                'submitted_at' => now(),
                'me_reviewed_by' => null,
                'me_reviewed_at' => null,
                'me_notes' => null,
                'baseline_locked_by' => null,
                'baseline_locked_at' => null,
            ];

            if ($plan) {
                if ($plan->isBaselineLocked()) {
                    throw new \RuntimeException('This technical plan is baseline-locked and cannot be revised.');
                }
                $plan->update($attrs);
                $this->resetEditableOutputs($plan, []);
            } else {
                $plan = MeTechnicalPlan::query()->create(array_merge($attrs, [
                    'budget_request_id' => $budgetRequest->id,
                ]));
            }

            $this->writeOutputs($plan, $outputs);
            $this->syncQuarters($plan);

            return $plan->fresh(['outputs', 'department']);
        });
    }

    /**
     * Independent departmental technical plan — routes straight to M&E review.
     *
     * @param  list<array{output: string, activity: string, costable_item?: ?string, planned: float|int|string, planned_unit?: ?string, quarter?: int|null}>  $outputs
     */
    public function submitIndependentPlan(
        Department $department,
        User $user,
        string $title,
        array $outputs,
        ?string $summary = null,
        ?string $fiscalYear = null,
        ?MeTechnicalPlan $existing = null,
    ): MeTechnicalPlan {
        return DB::transaction(function () use ($department, $user, $title, $outputs, $summary, $fiscalYear, $existing) {
            if ($existing) {
                if ((int) $existing->department_id !== (int) $department->id) {
                    abort(403);
                }
                if ($existing->isBaselineLocked()) {
                    throw new \RuntimeException('This technical plan is baseline-locked and cannot be revised.');
                }

                // Quarters M&E has already cleared are baseline; the department fills the
                // rest of the year around them instead of resubmitting the whole plan.
                $frozen = $existing->usesQuarterTags() ? $this->approvedQuarterNumbers($existing) : [];
                $submitted = array_values(array_filter(
                    $outputs,
                    fn ($row) => ! in_array((int) ($row['quarter'] ?? 0), $frozen, true)
                ));

                $existing->update([
                    'budget_request_id' => null,
                    'planning_cycle_id' => null,
                    'title' => $title,
                    'fiscal_year' => $fiscalYear ?: ($existing->fiscal_year ?: (string) now()->year),
                    'status' => 'me_review',
                    'summary' => $summary,
                    'submitted_by' => $user->id,
                    'submitted_at' => now(),
                ]);
                $offset = $this->resetEditableOutputs($existing, $frozen);
                $plan = $existing;
            } else {
                $frozen = [];
                $submitted = array_values($outputs);
                $offset = 0;
                $plan = MeTechnicalPlan::query()->create([
                    'budget_request_id' => null,
                    'planning_cycle_id' => null,
                    'department_id' => $department->id,
                    'title' => $title,
                    'fiscal_year' => $fiscalYear ?: (string) now()->year,
                    'status' => 'me_review',
                    'summary' => $summary,
                    'submitted_by' => $user->id,
                    'submitted_at' => now(),
                ]);
            }

            if ($submitted === []) {
                throw new \RuntimeException('Fill in at least one quarter that is not already approved by M&E.');
            }

            $this->writeOutputs($plan, $submitted, $offset);
            $this->syncQuarters($plan, $this->desiredQuarterStatuses($plan));
            $this->syncPlanStatusFromQuarters($plan);

            $fresh = $plan->fresh(['outputs', 'quarters', 'department']);
            $this->notifyMeOfficers($fresh);

            try {
                app(\App\Services\Sidebar\MeSidebarNotificationService::class)->broadcastCounts();
            } catch (\Throwable) {
                // Non-fatal.
            }

            return $fresh;
        });
    }

    protected function resetEditableOutputs(MeTechnicalPlan $plan, array $frozenQuarters): int
    {
        $plan->outputs()
            ->when($frozenQuarters !== [], function ($query) use ($frozenQuarters) {
                $query->where(function ($inner) use ($frozenQuarters) {
                    $inner->whereNull('quarter')->orWhereNotIn('quarter', $frozenQuarters);
                });
            })
            ->delete();

        $plan->unsetRelation('outputs');
        $plan->loadMissing('outputs');

        return $plan->outputs->count();
    }

    /**
     * @param  list<array{output: string, activity: string, costable_item?: ?string, planned: float|int|string, planned_unit?: ?string, quarter?: int|null}>  $outputs
     */
    protected function writeOutputs(MeTechnicalPlan $plan, array $outputs, int $orderOffset = 0): void
    {
        foreach (array_values($outputs) as $i => $row) {
            MePlanOutput::query()->create([
                'technical_plan_id' => $plan->id,
                'output' => trim((string) $row['output']),
                'activity' => trim((string) $row['activity']),
                'costable_item' => isset($row['costable_item']) ? trim((string) $row['costable_item']) : null,
                'planned' => round((float) $row['planned'], 2),
                'planned_unit' => isset($row['planned_unit']) ? trim((string) $row['planned_unit']) : null,
                'quarter' => isset($row['quarter']) && $row['quarter'] !== null && $row['quarter'] !== ''
                    ? (int) $row['quarter']
                    : null,
                'display_order' => $orderOffset + $i,
                'created_at' => now(),
            ]);
        }

        // Rows are written straight through the query builder, so the loaded relation is
        // stale from here on and the quarter sync would otherwise read the old set.
        $plan->unsetRelation('outputs');
    }

    /**
     * Ensure a technical plan exists for a budget request, then release it to M&E review.
     * Creates a fallback plan from budget line items when dual-ingest never ran.
     */
    public function ensureReleasedForBudget(BudgetRequest $budgetRequest, ?User $actor = null): MeTechnicalPlan
    {
        $budgetRequest->loadMissing(['department', 'planningCycle']);

        $plan = MeTechnicalPlan::query()
            ->where('budget_request_id', $budgetRequest->id)
            ->first();

        if (! $plan) {
            $department = $budgetRequest->department;
            if (! $department) {
                throw new \RuntimeException('Budget request has no department; cannot create an M&E plan.');
            }

            $outputs = $this->outputsFromBudgetLines($budgetRequest);
            $user = $actor
                ?? ($budgetRequest->submitted_by
                    ? User::query()->find($budgetRequest->submitted_by)
                    : null)
                ?? User::query()->orderBy('id')->first();

            if (! $user) {
                throw new \RuntimeException('Cannot create an M&E plan without a submitting user.');
            }

            $plan = $this->ingestFromBudgetSubmission(
                $budgetRequest,
                $department,
                $user,
                $outputs,
                'Auto-created from budget line items when Administration forwarded to Finance & M&E.',
            );
        } else {
            $this->hydrateOutputsFromBudget($plan);
        }

        return $this->releaseToMeReview($plan, $actor);
    }

    /**
     * Fill missing plan output rows from the linked budget line items.
     */
    public function hydrateOutputsFromBudget(MeTechnicalPlan $plan): MeTechnicalPlan
    {
        $plan->loadMissing(['outputs', 'budgetRequest']);

        if ($plan->outputs->isNotEmpty() || ! $plan->budgetRequest) {
            return $plan;
        }

        if ($plan->isBaselineLocked()) {
            return $plan;
        }

        foreach ($this->outputsFromBudgetLines($plan->budgetRequest) as $i => $row) {
            MePlanOutput::query()->create([
                'technical_plan_id' => $plan->id,
                'output' => $row['output'],
                'activity' => $row['activity'],
                'costable_item' => $row['costable_item'],
                'planned' => $row['planned'],
                'planned_unit' => $row['planned_unit'],
                'display_order' => $i,
                'created_at' => now(),
            ]);
        }

        return $plan->fresh(['outputs', 'budgetRequest', 'department']);
    }

    /**
     * @return list<array{output: string, activity: string, costable_item: ?string, planned: float, planned_unit: ?string}>
     */
    protected function outputsFromBudgetLines(BudgetRequest $budgetRequest): array
    {
        $lines = $budgetRequest->expenditureLines();
        $outputs = [];

        foreach ($lines as $line) {
            if (! is_array($line)) {
                continue;
            }
            $item = trim((string) ($line['item'] ?? $line['description'] ?? ''));
            if ($item === '') {
                continue;
            }
            $outputs[] = [
                'output' => $item,
                'activity' => trim((string) ($line['description'] ?? '')) !== ''
                    ? trim((string) $line['description'])
                    : 'Deliver / procure '.$item,
                'costable_item' => $item,
                'planned' => round((float) ($line['quantity'] ?? $line['total'] ?? 1), 2),
                'planned_unit' => isset($line['unit_of_measure']) ? trim((string) $line['unit_of_measure']) : null,
            ];
        }

        if ($outputs === []) {
            $outputs[] = [
                'output' => $budgetRequest->title,
                'activity' => 'Implement activities under '.$budgetRequest->title,
                'costable_item' => null,
                'planned' => round((float) $budgetRequest->requested_amount, 2),
                'planned_unit' => 'KES',
            ];
        }

        return $outputs;
    }

    /**
     * Release a linked technical plan into the M&E review queue (after Administration clearance).
     */
    public function releaseToMeReview(MeTechnicalPlan $plan, ?User $actor = null): MeTechnicalPlan
    {
        if ($plan->isBaselineLocked()) {
            throw new \RuntimeException('This technical plan is baseline-locked and cannot be released to M&E.');
        }

        if (in_array($plan->status, ['me_approved', 'baseline_locked'], true)) {
            return $plan;
        }

        if ($plan->status !== 'me_review') {
            $plan->update([
                'status' => 'me_review',
                'me_reviewed_by' => null,
                'me_reviewed_at' => null,
                'me_notes' => null,
                'submitted_at' => $plan->submitted_at ?? now(),
            ]);
        }

        $fresh = $plan->fresh(['department']);
        $this->notifyMeOfficers($fresh);

        try {
            app(\App\Services\Sidebar\MeSidebarNotificationService::class)->broadcastCounts();
        } catch (\Throwable) {
            // Non-fatal.
        }

        return $fresh;
    }

    public function holdAfterAdminReturn(MeTechnicalPlan $plan): MeTechnicalPlan
    {
        if ($plan->isBaselineLocked() || in_array($plan->status, ['me_approved', 'baseline_locked'], true)) {
            return $plan;
        }

        $plan->update([
            'status' => 'draft',
            'me_reviewed_by' => null,
            'me_reviewed_at' => null,
        ]);

        return $plan->fresh();
    }

    /**
     * Clear every quarter the department has filed so far. The year only baseline-locks
     * once all four quarters are filled and approved.
     */
    public function approveByMe(MeTechnicalPlan $plan, User $user, ?string $notes = null): MeTechnicalPlan
    {
        if (! in_array($plan->status, ['me_review', 'returned', 'me_approved'], true)) {
            throw new \RuntimeException('Only plans in M&E review can be approved.');
        }

        $plan->loadMissing(['outputs', 'quarters']);

        if ($plan->usesQuarterTags()) {
            $staff = $this->staffPortal->staffForUser($user);
            $pending = $plan->quarters->filter(fn (MeQuarter $q) => $q->status === 'me_review');

            if ($pending->isEmpty()) {
                return $plan;
            }

            foreach ($pending as $quarter) {
                $this->stampQuarter($quarter, 'me_approved', $staff, $notes);
            }

            $this->rememberPlanReviewer($plan, $staff, $notes);
            $this->syncPlanStatusFromQuarters($plan);

            $locked = $this->tryBaselineLock($plan->fresh(['budgetRequest', 'outputs', 'quarters']), $user);

            return $locked ?? $plan->fresh(['outputs', 'quarters', 'budgetRequest']);
        }

        $staff = $this->staffPortal->staffForUser($user);
        $this->rememberPlanReviewer($plan, $staff, $notes, 'me_approved');

        $this->tryBaselineLock($plan->fresh(['budgetRequest']), $user);

        return $plan->fresh(['outputs', 'quarters', 'budgetRequest']);
    }

    public function approveQuarter(MeTechnicalPlan $plan, int $quarterNumber, User $user, ?string $notes = null): MeTechnicalPlan
    {
        $quarter = $this->quarterRow($plan, $quarterNumber);

        if ($quarter->isApproved()) {
            return $plan->fresh(['outputs', 'quarters']);
        }

        if (! $this->quarterHasOutputs($plan, $quarterNumber)) {
            throw new \RuntimeException($quarter->label().' has no outputs to approve yet.');
        }

        $staff = $this->staffPortal->staffForUser($user);
        $this->stampQuarter($quarter, 'me_approved', $staff, $notes);
        $this->rememberPlanReviewer($plan, $staff, $notes);
        $this->syncPlanStatusFromQuarters($plan);

        $locked = $this->tryBaselineLock($plan->fresh(['budgetRequest', 'outputs', 'quarters']), $user);

        return $locked ?? $plan->fresh(['outputs', 'quarters', 'budgetRequest']);
    }

    public function returnQuarter(MeTechnicalPlan $plan, int $quarterNumber, User $user, string $notes): MeTechnicalPlan
    {
        $quarter = $this->quarterRow($plan, $quarterNumber);

        if ($quarter->isApproved()) {
            throw new \RuntimeException($quarter->label().' is already approved by M&E.');
        }

        if (! $this->quarterHasOutputs($plan, $quarterNumber)) {
            throw new \RuntimeException($quarter->label().' has nothing filed to return.');
        }

        $staff = $this->staffPortal->staffForUser($user);
        $this->stampQuarter($quarter, 'returned', $staff, $notes);
        $this->rememberPlanReviewer($plan, $staff, $notes);
        $this->syncPlanStatusFromQuarters($plan);

        return $plan->fresh(['outputs', 'quarters']);
    }

    public function returnToDepartment(MeTechnicalPlan $plan, User $user, string $notes): MeTechnicalPlan
    {
        $staff = $this->staffPortal->staffForUser($user);
        $plan->loadMissing(['outputs', 'quarters']);

        if ($plan->usesQuarterTags()) {
            // Approved quarters stay cleared; only the quarters still on review go back.
            foreach ($plan->quarters as $quarter) {
                if ($quarter->isApproved() || ! $this->quarterHasOutputs($plan, (int) $quarter->quarter_number)) {
                    continue;
                }
                $this->stampQuarter($quarter, 'returned', $staff, $notes);
            }

            $this->rememberPlanReviewer($plan, $staff, $notes);
            $this->syncPlanStatusFromQuarters($plan);

            return $plan->fresh(['outputs', 'quarters']);
        }

        $this->rememberPlanReviewer($plan, $staff, $notes, 'returned');

        return $plan->fresh();
    }

    /**
     * Lock baseline when M&E has approved.
     * Linked budget plans also require the budget to be approved; independent plans lock on M&E approval alone.
     * A quarter-tagged plan only locks once Q1-Q4 are all filled and cleared.
     */
    public function tryBaselineLock(MeTechnicalPlan $plan, ?User $user = null): ?MeTechnicalPlan
    {
        $plan->loadMissing(['budgetRequest', 'outputs', 'quarters']);

        if ($plan->isBaselineLocked()) {
            return $plan;
        }

        if ($plan->status !== 'me_approved') {
            return null;
        }

        $budget = $plan->budgetRequest;
        if ($budget && $budget->status !== 'approved') {
            return null;
        }

        if ($plan->usesQuarterTags() && ! $plan->allQuartersApproved()) {
            return null;
        }

        return $this->lockBaseline($plan, $user);
    }

    public function lockBaseline(MeTechnicalPlan $plan, ?User $user = null): MeTechnicalPlan
    {
        return DB::transaction(function () use ($plan, $user) {
            $plan->loadMissing('outputs');
            $staff = $user ? $this->staffPortal->staffForUser($user) : null;

            $plan->update([
                'status' => 'baseline_locked',
                'baseline_locked_by' => $staff?->id,
                'baseline_locked_at' => now(),
            ]);

            $this->partitionIntoQuarters($plan);

            return $plan->fresh(['outputs', 'quarters']);
        });
    }

    public function partitionIntoQuarters(MeTechnicalPlan $plan): void
    {
        $this->syncQuarters($plan);
    }

    /**
     * Create the quarter rows a plan is missing. Never removes or re-opens a quarter
     * M&E has cleared, because its reports are already filed against it.
     *
     * @param  array<int, string>  $statuses  quarter number => requested status
     */
    public function syncQuarters(MeTechnicalPlan $plan, array $statuses = []): void
    {
        $plan->loadMissing('quarters');
        $rows = $plan->quarters->keyBy('quarter_number');

        foreach ([1, 2, 3, 4] as $number) {
            $row = $rows[$number] ?? null;

            if (! $row) {
                [$periodStart, $periodEnd] = $this->quarterPeriod($plan, $number);

                MeQuarter::query()->create([
                    'technical_plan_id' => $plan->id,
                    'quarter_number' => $number,
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                    'status' => $statuses[$number] ?? 'open',
                    'created_at' => now(),
                ]);

                continue;
            }

            if ($row->isApproved()) {
                continue;
            }

            $desired = $statuses[$number] ?? null;
            if ($desired !== null && $desired !== $row->status) {
                $row->update(['status' => $desired]);
            }
        }

        $plan->unsetRelation('quarters');
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function quarterPeriod(MeTechnicalPlan $plan, int $number): array
    {
        $start = Carbon::now()->startOfYear();
        if ($plan->fiscal_year && preg_match('/(\d{4})/', (string) $plan->fiscal_year, $m)) {
            $start = Carbon::create((int) $m[1], 1, 1)->startOfDay();
        }

        $periodStart = $start->copy()->addMonths(($number - 1) * 3)->startOfMonth();

        return [$periodStart, $periodStart->copy()->addMonths(2)->endOfMonth()];
    }

    protected function quarterRow(MeTechnicalPlan $plan, int $quarterNumber): MeQuarter
    {
        $this->syncQuarters($plan);

        $quarter = $plan->quarters()->where('quarter_number', $quarterNumber)->first();

        if (! $quarter) {
            abort(404);
        }

        return $quarter;
    }

    protected function quarterHasOutputs(MeTechnicalPlan $plan, int $quarterNumber): bool
    {
        return MePlanOutput::query()
            ->where('technical_plan_id', $plan->id)
            ->where('quarter', $quarterNumber)
            ->exists();
    }

    /**
     * @return list<int>
     */
    protected function approvedQuarterNumbers(MeTechnicalPlan $plan): array
    {
        $plan->loadMissing('quarters');

        return $plan->quarters
            ->where('status', 'me_approved')
            ->pluck('quarter_number')
            ->map(fn ($number) => (int) $number)
            ->values()
            ->all();
    }

    protected function stampQuarter(MeQuarter $quarter, string $status, ?Staff $staff, ?string $notes): void
    {
        $quarter->update([
            'status' => $status,
            'reviewed_by' => $staff?->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);
    }

    protected function rememberPlanReviewer(
        MeTechnicalPlan $plan,
        ?Staff $staff,
        ?string $notes,
        ?string $status = null,
    ): void {
        $plan->update(array_filter([
            'me_reviewed_by' => $staff?->id,
            'me_reviewed_at' => now(),
            'me_notes' => $notes,
            'status' => $status,
        ], fn ($value) => $value !== null));
    }

    /**
     * @return array<int, string>
     */
    protected function desiredQuarterStatuses(MeTechnicalPlan $plan): array
    {
        $plan->loadMissing('outputs');

        $filled = $plan->outputs
            ->pluck('quarter')
            ->filter()
            ->map(fn ($number) => (int) $number)
            ->unique();

        $statuses = [];
        foreach ([1, 2, 3, 4] as $number) {
            $statuses[$number] = $filled->contains($number) ? 'me_review' : 'open';
        }

        return $statuses;
    }

    /**
     * Keep the plan status in step with its quarters: nothing is cleared for the whole
     * year until every quarter of the year has been approved.
     */
    protected function syncPlanStatusFromQuarters(MeTechnicalPlan $plan): void
    {
        $plan->load(['outputs', 'quarters']);

        if (! $plan->usesQuarterTags()) {
            return;
        }

        $statuses = $plan->quarters->pluck('status');

        $status = match (true) {
            $plan->allQuartersApproved() => 'me_approved',
            $statuses->contains('me_review') => 'me_review',
            $statuses->contains('returned') => 'returned',
            $statuses->contains('me_approved') => 'me_approved',
            default => 'me_review',
        };

        if ($plan->status !== $status) {
            $plan->update(['status' => $status]);
        }
    }

    /**
     * Ensure a draft quarterly report exists with standardised grid lines.
     * Quarter-tagged outputs use full planned values; legacy (no quarter) still divide annual/4.
     */
    public function ensureQuarterlyReportDraft(MeTechnicalPlan $plan, MeQuarter $quarter): MeQuarterlyReport
    {
        $plan->loadMissing('outputs');

        $report = MeQuarterlyReport::query()->firstOrCreate(
            [
                'quarter_id' => $quarter->id,
                'department_id' => $plan->department_id,
            ],
            [
                'technical_plan_id' => $plan->id,
                'status' => 'draft',
            ]
        );

        if ($report->lines()->exists()) {
            return $report->load('lines');
        }

        $hasQuarterTags = $plan->outputs->contains(fn ($o) => $o->quarter !== null);
        $outputs = $hasQuarterTags
            ? $plan->outputs->filter(fn ($o) => (int) $o->quarter === (int) $quarter->quarter_number)->values()
            : $plan->outputs;

        foreach ($outputs as $i => $output) {
            $planned = $hasQuarterTags
                ? round((float) $output->planned, 2)
                : round(((float) $output->planned) / 4, 2);
            MeQuarterlyReportLine::query()->create([
                'quarterly_report_id' => $report->id,
                'plan_output_id' => $output->id,
                'output' => $output->output,
                'activity' => $output->activity,
                'costable_item' => $output->costable_item,
                'planned' => $planned,
                'achieved' => 0,
                'deviation' => 0 - $planned,
                'display_order' => $i,
            ]);
        }

        return $report->load('lines');
    }

    protected function notifyMeOfficers(MeTechnicalPlan $plan): void
    {
        try {
            $userIds = DB::table('user_roles as ur')
                ->join('roles as r', 'r.id', '=', 'ur.role_id')
                ->whereIn('r.role_name', [
                    'Monitoring and Evaluation Officer',
                    'Assistant Monitoring and Evaluation Officer',
                    'Super Admin',
                ])
                ->pluck('ur.user_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            if ($userIds === []) {
                return;
            }

            $this->notifications->notifyUsers(
                $userIds,
                'Technical plan awaiting M&E review',
                ($plan->department?->dept_name ?? 'A department').(
                    $plan->budget_request_id
                        ? ' technical plan was released by Administration for M&E review (alongside Finance).'
                        : ' submitted an independent technical plan for M&E review.'
                ),
                'me_technical_plan',
                (string) $plan->id,
                'high',
                route('monitoring_evaluation.plans.show', $plan),
            );
        } catch (\Throwable) {
            // Non-fatal.
        }
    }
}

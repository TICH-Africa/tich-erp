<?php

namespace App\Services\Me;

use App\Models\Administration\BudgetRequest;
use App\Models\Department;
use App\Models\Me\MePlanOutput;
use App\Models\Me\MeQuarter;
use App\Models\Me\MeQuarterlyReport;
use App\Models\Me\MeQuarterlyReportLine;
use App\Models\Me\MeTechnicalPlan;
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
                $plan->outputs()->delete();
                $plan->quarters()->delete();
            } else {
                $plan = MeTechnicalPlan::query()->create(array_merge($attrs, [
                    'budget_request_id' => $budgetRequest->id,
                ]));
            }

            $this->writeOutputs($plan, $outputs);

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
                if (! in_array($existing->status, ['draft', 'returned'], true)) {
                    throw new \RuntimeException('Only draft or returned plans can be revised.');
                }

                $existing->update([
                    'budget_request_id' => null,
                    'planning_cycle_id' => null,
                    'title' => $title,
                    'fiscal_year' => $fiscalYear ?: ($existing->fiscal_year ?: (string) now()->year),
                    'status' => 'me_review',
                    'summary' => $summary,
                    'submitted_by' => $user->id,
                    'submitted_at' => now(),
                    'me_reviewed_by' => null,
                    'me_reviewed_at' => null,
                    'me_notes' => null,
                ]);
                $existing->outputs()->delete();
                $existing->quarters()->delete();
                $plan = $existing;
            } else {
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

            $this->writeOutputs($plan, $outputs);
            $fresh = $plan->fresh(['outputs', 'department']);
            $this->notifyMeOfficers($fresh);

            try {
                app(\App\Services\Sidebar\MeSidebarNotificationService::class)->broadcastCounts();
            } catch (\Throwable) {
                // Non-fatal.
            }

            return $fresh;
        });
    }

    /**
     * @param  list<array{output: string, activity: string, costable_item?: ?string, planned: float|int|string, planned_unit?: ?string, quarter?: int|null}>  $outputs
     */
    protected function writeOutputs(MeTechnicalPlan $plan, array $outputs): void
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
                'display_order' => $i,
                'created_at' => now(),
            ]);
        }
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

    public function approveByMe(MeTechnicalPlan $plan, User $user, ?string $notes = null): MeTechnicalPlan
    {
        if (! in_array($plan->status, ['me_review', 'returned'], true)) {
            throw new \RuntimeException('Only plans in M&E review can be approved.');
        }

        $staff = $this->staffPortal->staffForUser($user);

        $plan->update([
            'status' => 'me_approved',
            'me_reviewed_by' => $staff?->id,
            'me_reviewed_at' => now(),
            'me_notes' => $notes,
        ]);

        $this->tryBaselineLock($plan->fresh(['budgetRequest']), $user);

        return $plan->fresh(['outputs', 'quarters', 'budgetRequest']);
    }

    public function returnToDepartment(MeTechnicalPlan $plan, User $user, string $notes): MeTechnicalPlan
    {
        $staff = $this->staffPortal->staffForUser($user);

        $plan->update([
            'status' => 'returned',
            'me_reviewed_by' => $staff?->id,
            'me_reviewed_at' => now(),
            'me_notes' => $notes,
        ]);

        return $plan->fresh();
    }

    /**
     * Lock baseline when M&E has approved.
     * Linked budget plans also require the budget to be approved; independent plans lock on M&E approval alone.
     */
    public function tryBaselineLock(MeTechnicalPlan $plan, ?User $user = null): ?MeTechnicalPlan
    {
        $plan->loadMissing('budgetRequest', 'outputs');

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
        $plan->quarters()->delete();

        $start = Carbon::now()->startOfYear();
        if ($plan->fiscal_year && preg_match('/(\d{4})/', (string) $plan->fiscal_year, $m)) {
            $start = Carbon::create((int) $m[1], 1, 1)->startOfDay();
        }

        for ($q = 1; $q <= 4; $q++) {
            $periodStart = $start->copy()->addMonths(($q - 1) * 3)->startOfMonth();
            $periodEnd = $periodStart->copy()->addMonths(2)->endOfMonth();

            MeQuarter::query()->create([
                'technical_plan_id' => $plan->id,
                'quarter_number' => $q,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'status' => 'open',
                'created_at' => now(),
            ]);
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

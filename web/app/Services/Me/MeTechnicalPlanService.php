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
     * @param  list<array{output: string, activity: string, costable_item?: ?string, planned: float|int|string, planned_unit?: ?string}>  $outputs
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
                'status' => 'me_review',
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

            foreach (array_values($outputs) as $i => $row) {
                MePlanOutput::query()->create([
                    'technical_plan_id' => $plan->id,
                    'output' => trim((string) $row['output']),
                    'activity' => trim((string) $row['activity']),
                    'costable_item' => isset($row['costable_item']) ? trim((string) $row['costable_item']) : null,
                    'planned' => round((float) $row['planned'], 2),
                    'planned_unit' => isset($row['planned_unit']) ? trim((string) $row['planned_unit']) : null,
                    'display_order' => $i,
                    'created_at' => now(),
                ]);
            }

            $this->notifyMeOfficers($plan);

            return $plan->fresh(['outputs', 'department']);
        });
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
     * Lock baseline when M&E has approved and linked budget is approved by Admin/Finance/CEO path.
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
        if (! $budget || $budget->status !== 'approved') {
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
     * Ensure a draft quarterly report exists with standardised grid lines (planned = annual/4).
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

        foreach ($plan->outputs as $i => $output) {
            $planned = round(((float) $output->planned) / 4, 2);
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
                ($plan->department?->dept_name ?? 'A department').' submitted a technical plan with their budget request.',
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

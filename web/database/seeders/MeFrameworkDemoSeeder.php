<?php

namespace Database\Seeders;

use App\Models\Administration\PlanningCycle;
use App\Models\Department;
use App\Models\Me\MePlanOutput;
use App\Models\Me\MePolicy;
use App\Models\Me\MePolicySignoff;
use App\Models\Me\MeQuarterlyReport;
use App\Models\Me\MeTechnicalPlan;
use App\Models\Staff;
use App\Models\User;
use App\Services\Me\MeHealthScoreService;
use App\Services\Me\MeTechnicalPlanService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class MeFrameworkDemoSeeder extends Seeder
{
    public const POLICY_TITLE = 'Institutional Monitoring & Evaluation Policy';

    public function run(): void
    {
        if (! Schema::hasTable('me_policies') || ! Schema::hasTable('me_technical_plans')) {
            $this->command?->warn('M&E tables missing - skipped MeFrameworkDemoSeeder.');

            return;
        }

        $user = $this->resolveMeUser();
        if (! $user) {
            $this->command?->warn('No staff user available for M&E seeding (run MonitoringEvaluationDemoSeeder first).');

            return;
        }

        $staff = Staff::query()->where('user_id', $user->id)->first()
            ?? Staff::query()->where('employee_number', 'EMP-MNE-001')->first();

        $fiscalYear = (string) now()->year;
        $policy = $this->ensurePublishedPolicy($staff, $fiscalYear);
        $cycle = $this->ensurePlanningCycle($fiscalYear);

        $departments = Department::query()
            ->active()
            ->whereNull('parent_dept_id')
            ->whereIn('dept_code', ['HR', 'FIN', 'QA', 'ACAD', 'ICTO', 'PRC', 'ADM', 'RES', 'MNE', 'M&E', 'ME'])
            ->orderBy('dept_name')
            ->get();

        if ($departments->isEmpty()) {
            $departments = Department::query()->active()->whereNull('parent_dept_id')->orderBy('dept_name')->limit(6)->get();
        }

        $plansService = app(MeTechnicalPlanService::class);
        $createdPlans = 0;

        foreach ($departments as $department) {
            $this->ensureHodSignoff($policy, $department, $staff, $user);

            $planTitle = $this->departmentPlanTitle($department, $fiscalYear);
            if (MeTechnicalPlan::query()->where('title', $planTitle)->exists()) {
                $this->command?->info("Technical plan for {$department->dept_code} already exists - skipped.");

                continue;
            }

            $plan = MeTechnicalPlan::query()->create([
                'budget_request_id' => null,
                'planning_cycle_id' => $cycle?->id,
                'department_id' => $department->id,
                'title' => $planTitle,
                'fiscal_year' => $fiscalYear,
                'status' => 'me_approved',
                'summary' => "Annual technical plan for {$department->dept_name}, aligned to the institutional results framework and quarterly reporting cycle.",
                'submitted_by' => $user->id,
                'submitted_at' => now()->subWeeks(3),
                'me_reviewed_by' => $staff?->id,
                'me_reviewed_at' => now()->subWeeks(2),
                'me_notes' => 'Plan reviewed and cleared for baseline lock pending approved budget linkage where applicable.',
            ]);

            foreach ($this->outputsForDepartment($department) as $index => $row) {
                MePlanOutput::query()->create([
                    'technical_plan_id' => $plan->id,
                    'output' => $row['output'],
                    'activity' => $row['activity'],
                    'costable_item' => $row['costable_item'],
                    'planned' => $row['planned'],
                    'planned_unit' => $row['planned_unit'],
                    'display_order' => $index,
                    'created_at' => now(),
                ]);
            }

            $plansService->lockBaseline($plan->fresh('outputs'), $user);
            $plan = $plan->fresh(['outputs', 'quarters']);

            $q1 = $plan->quarters->firstWhere('quarter_number', 1);
            if ($q1) {
                $report = $plansService->ensureQuarterlyReportDraft($plan, $q1);
                $this->fillSampleAchievements($report, $department);
                if (in_array($department->dept_code, ['HR', 'FIN', 'QA', 'ACAD'], true)) {
                    $report->update([
                        'status' => 'submitted',
                        'submitted_by' => $user->id,
                        'submitted_at' => now()->subDays(2),
                    ]);
                }
            }

            $createdPlans++;
            $this->command?->info(sprintf(
                'Created M&E technical plan #%d for %s with %d outputs.',
                $plan->id,
                $department->dept_code,
                $plan->outputs->count(),
            ));
        }

        try {
            app(MeHealthScoreService::class)->recalculateAll($fiscalYear);
        } catch (\Throwable $e) {
            $this->command?->warn('Health score recalculation skipped: '.$e->getMessage());
        }

        $this->command?->info(sprintf(
            'M&E seed complete: policy #%d, %d new technical plan(s) for FY %s.',
            $policy->id,
            $createdPlans,
            $fiscalYear,
        ));
    }

    private function ensurePublishedPolicy(?Staff $staff, string $fiscalYear): MePolicy
    {
        $existing = MePolicy::query()
            ->where('title', self::POLICY_TITLE)
            ->orWhere(function ($q) use ($fiscalYear) {
                $q->where('fiscal_year', $fiscalYear)->where('status', 'published');
            })
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            if ($existing->status !== 'published') {
                $existing->update([
                    'status' => 'published',
                    'published_at' => $existing->published_at ?? now(),
                ]);
            }

            return $existing->fresh();
        }

        $relative = 'me-policies/'.$fiscalYear.'/institutional-me-policy.txt';
        Storage::disk('public')->put(
            $relative,
            "TICH Institutional Monitoring & Evaluation Policy\n"
            ."Fiscal year: {$fiscalYear}\n\n"
            ."1. Purpose\n"
            ."This policy establishes the framework for results-based planning, quarterly performance reporting,\n"
            ."verification by M&E, and delivery of consolidated reports to institutional leadership.\n\n"
            ."2. Scope\n"
            ."Applies to all departments submitting annual budgets and technical plans through the ERP.\n\n"
            ."3. Responsibilities\n"
            ."- HODs: sign this policy, submit plans, report quarterly achievements.\n"
            ."- M&E: review plans, verify reports, maintain PIME dashboards.\n"
            ."- CEO: receive and sign consolidated quarterly performance packs.\n\n"
            ."4. Reporting cycle\n"
            ."Q1–Q4 reports are due within 15 working days after each quarter end.\n"
        );

        return MePolicy::query()->create([
            'fiscal_year' => $fiscalYear,
            'title' => self::POLICY_TITLE,
            'version' => '1.0',
            'file_path' => 'storage/'.$relative,
            'description' => 'Official institutional M&E policy governing departmental budgeting, technical planning, and quarterly performance reporting.',
            'effective_date' => now()->startOfYear()->toDateString(),
            'status' => 'published',
            'uploaded_by' => $staff?->id,
            'uploaded_at' => now()->subMonths(1),
            'published_at' => now()->subMonths(1),
        ]);
    }

    private function ensurePlanningCycle(string $fiscalYear): ?PlanningCycle
    {
        if (! Schema::hasTable('admin_planning_cycles')) {
            return null;
        }

        $cycle = PlanningCycle::query()
            ->where('fiscal_year', $fiscalYear)
            ->orWhere('cycle_code', 'like', '%'.$fiscalYear.'%')
            ->orderByDesc('id')
            ->first();

        if ($cycle) {
            return $cycle;
        }

        return PlanningCycle::query()->create([
            'cycle_code' => 'FY-'.$fiscalYear,
            'title' => 'Annual planning '.$fiscalYear,
            'plan_tier' => 'annual',
            'fiscal_year' => (int) $fiscalYear,
            'period_start' => now()->startOfYear()->toDateString(),
            'period_end' => now()->endOfYear()->toDateString(),
            'requisition_deadline' => now()->startOfYear()->addMonths(2)->endOfDay(),
            'status' => 'open',
            'notes' => 'Annual planning cycle for the current fiscal year.',
        ]);
    }

    private function ensureHodSignoff(MePolicy $policy, Department $department, ?Staff $fallbackStaff, User $user): void
    {
        $hod = $department->hod_id
            ? Staff::query()->find($department->hod_id)
            : null;
        $signer = $hod ?? $fallbackStaff;
        if (! $signer) {
            return;
        }

        MePolicySignoff::query()->firstOrCreate(
            [
                'policy_id' => $policy->id,
                'department_id' => $department->id,
                'staff_id' => $signer->id,
            ],
            [
                'user_id' => $user->id,
                'signed_name' => trim($signer->first_name.' '.$signer->surname),
                'employee_number' => $signer->employee_number,
                'signature' => null,
                'ip_address' => '127.0.0.1',
                'signed_at' => now()->subWeeks(4),
            ]
        );
    }

    private function fillSampleAchievements(MeQuarterlyReport $report, Department $department): void
    {
        $report->loadMissing('lines');
        $factor = match (strtoupper((string) $department->dept_code)) {
            'HR', 'QA' => 0.92,
            'FIN', 'ACAD' => 0.78,
            'ICTO', 'ICT' => 0.65,
            default => 0.55,
        };

        foreach ($report->lines as $line) {
            $planned = (float) $line->planned;
            $achieved = round($planned * $factor, 2);
            $line->update([
                'achieved' => $achieved,
                'deviation' => round($achieved - $planned, 2),
            ]);
        }
    }

    public static function departmentPlanTitle(Department $department, ?string $fiscalYear = null): string
    {
        $year = $fiscalYear ?: (string) now()->year;

        return "{$department->dept_name} Technical Plan FY{$year}";
    }

    private function resolveMeUser(): ?User
    {
        $staff = Staff::query()
            ->where('employee_number', 'EMP-MNE-001')
            ->orWhere('job_title', 'like', '%Monitoring%')
            ->orderBy('id')
            ->first();

        if ($staff?->user_id) {
            $user = User::query()->find($staff->user_id);
            if ($user) {
                if (! $user->staff_id) {
                    $user->update(['staff_id' => $staff->id]);
                }

                return $user->fresh();
            }
        }

        return User::query()
            ->whereNotNull('staff_id')
            ->where('is_active', 1)
            ->orderBy('id')
            ->first();
    }

    /**
     * @return list<array{output: string, activity: string, costable_item: string, planned: float, planned_unit: string}>
     */
    private function outputsForDepartment(Department $department): array
    {
        $code = strtoupper((string) $department->dept_code);

        return match ($code) {
            'HR' => [
                ['output' => 'Workforce plan updated', 'activity' => 'Complete establishment review and vacancy prioritisation', 'costable_item' => 'HR planning workshops', 'planned' => 4, 'planned_unit' => 'reviews'],
                ['output' => 'Staff appraisals completed', 'activity' => 'Run annual performance appraisal cycle', 'costable_item' => 'Appraisal stationery & training', 'planned' => 120, 'planned_unit' => 'staff'],
                ['output' => 'CPD sessions delivered', 'activity' => 'Coordinate institutional CPD calendar with departments', 'costable_item' => 'Facilitator fees', 'planned' => 8, 'planned_unit' => 'sessions'],
                ['output' => 'Recruitment files closed on time', 'activity' => 'Fill approved critical posts within SLA', 'costable_item' => 'Advertisement & panels', 'planned' => 10, 'planned_unit' => 'posts'],
            ],
            'FIN' => [
                ['output' => 'Monthly reconciliations completed', 'activity' => 'Reconcile bank and fee ledgers', 'costable_item' => 'Finance system licences', 'planned' => 12, 'planned_unit' => 'months'],
                ['output' => 'Budget variance packs issued', 'activity' => 'Issue quarterly budget holder variance reports', 'costable_item' => 'Reporting tools', 'planned' => 4, 'planned_unit' => 'packs'],
                ['output' => 'Fee statements accuracy', 'activity' => 'Validate student fee statements before exam clearance', 'costable_item' => 'Overtime / audit support', 'planned' => 95, 'planned_unit' => '% accuracy'],
                ['output' => 'Statutory remittances on time', 'activity' => 'Remit PAYE/NSSF/SHIF within deadlines', 'costable_item' => 'Compliance filings', 'planned' => 12, 'planned_unit' => 'months'],
            ],
            'QA' => [
                ['output' => 'Assessment sheets dispatched', 'activity' => 'Design and dispatch institutional QA sheets', 'costable_item' => 'QA printing & facilitation', 'planned' => 6, 'planned_unit' => 'sheets'],
                ['output' => 'Department responses reviewed', 'activity' => 'Review submitted evidence and scores', 'costable_item' => 'Review panels', 'planned' => 15, 'planned_unit' => 'departments'],
                ['output' => 'Corrective actions verified', 'activity' => 'Follow up and close CAPAs from non-compliance', 'costable_item' => 'Verification visits', 'planned' => 20, 'planned_unit' => 'actions'],
                ['output' => 'Capacity sessions held', 'activity' => 'Deliver QA capacity-building workshops', 'costable_item' => 'Venue & materials', 'planned' => 4, 'planned_unit' => 'sessions'],
            ],
            'ACAD' => [
                ['output' => 'Timetables published on time', 'activity' => 'Publish conflict-checked master timetables', 'costable_item' => 'Timetabling tools', 'planned' => 2, 'planned_unit' => 'semesters'],
                ['output' => 'Exam cycles completed', 'activity' => 'Run CATs and final exams per academic calendar', 'costable_item' => 'Exam materials & invigilation', 'planned' => 2, 'planned_unit' => 'cycles'],
                ['output' => 'Results released within SLA', 'activity' => 'Process and release approved results', 'costable_item' => 'Results processing', 'planned' => 90, 'planned_unit' => '% on time'],
                ['output' => 'Programme reviews completed', 'activity' => 'Support departmental curriculum self-assessment', 'costable_item' => 'Review workshops', 'planned' => 5, 'planned_unit' => 'programmes'],
            ],
            'ICTO', 'ICT' => [
                ['output' => 'ERP uptime target met', 'activity' => 'Monitor and restore core systems', 'costable_item' => 'Hosting & support', 'planned' => 99, 'planned_unit' => '% uptime'],
                ['output' => 'Helpdesk tickets closed', 'activity' => 'Resolve ICT tickets within SLA', 'costable_item' => 'Helpdesk tools', 'planned' => 800, 'planned_unit' => 'tickets'],
                ['output' => 'Backups verified', 'activity' => 'Run and test restore drills', 'costable_item' => 'Backup storage', 'planned' => 4, 'planned_unit' => 'drills'],
                ['output' => 'Security patches applied', 'activity' => 'Patch critical servers and endpoints', 'costable_item' => 'Security licences', 'planned' => 12, 'planned_unit' => 'cycles'],
            ],
            'PRC' => [
                ['output' => 'Procurement plan executed', 'activity' => 'Source against approved annual plan', 'costable_item' => 'Tender advertising', 'planned' => 80, 'planned_unit' => '% of plan'],
                ['output' => 'LPOs issued with full docs', 'activity' => 'Ensure complete evaluation & award files', 'costable_item' => 'Committee sittings', 'planned' => 40, 'planned_unit' => 'LPOs'],
                ['output' => 'GRN compliance', 'activity' => 'Inspect and receive goods against specs', 'costable_item' => 'Inspection tools', 'planned' => 95, 'planned_unit' => '% compliant'],
                ['output' => 'Stock counts completed', 'activity' => 'Conduct quarterly inventory counts', 'costable_item' => 'Stores labour', 'planned' => 4, 'planned_unit' => 'counts'],
            ],
            'ADM' => [
                ['output' => 'Committee meetings supported', 'activity' => 'Organise agendas, venues, and minutes', 'costable_item' => 'Meeting logistics', 'planned' => 24, 'planned_unit' => 'meetings'],
                ['output' => 'Facilities issues closed', 'activity' => 'Track and resolve estate/room issues', 'costable_item' => 'Minor works', 'planned' => 50, 'planned_unit' => 'tickets'],
                ['output' => 'Correspondence turnaround', 'activity' => 'Route and close official correspondence', 'costable_item' => 'Registry supplies', 'planned' => 90, 'planned_unit' => '% within SLA'],
                ['output' => 'Emergency drills held', 'activity' => 'Coordinate fire/safety drills', 'costable_item' => 'Safety materials', 'planned' => 2, 'planned_unit' => 'drills'],
            ],
            'RES' => [
                ['output' => 'Ethics reviews completed', 'activity' => 'Review student/staff research proposals', 'costable_item' => 'Ethics board sittings', 'planned' => 30, 'planned_unit' => 'proposals'],
                ['output' => 'Supervision progress logged', 'activity' => 'Monitor research candidate progress meetings', 'costable_item' => 'Supervision tools', 'planned' => 40, 'planned_unit' => 'candidates'],
                ['output' => 'Research workshops held', 'activity' => 'Deliver methodology capacity sessions', 'costable_item' => 'Workshop costs', 'planned' => 4, 'planned_unit' => 'workshops'],
                ['output' => 'Repository records updated', 'activity' => 'Catalogue research outputs', 'costable_item' => 'Repository hosting', 'planned' => 25, 'planned_unit' => 'outputs'],
            ],
            'MNE', 'M&E', 'ME' => [
                ['output' => 'Policy sign-offs completed', 'activity' => 'Track HOD digital sign-off of M&E policy', 'costable_item' => 'Policy dissemination', 'planned' => 12, 'planned_unit' => 'departments'],
                ['output' => 'Technical plans reviewed', 'activity' => 'Review and approve departmental technical plans', 'costable_item' => 'Review panels', 'planned' => 12, 'planned_unit' => 'plans'],
                ['output' => 'Quarterly reports verified', 'activity' => 'Verify Q1–Q4 departmental reports', 'costable_item' => 'Verification visits', 'planned' => 48, 'planned_unit' => 'reports'],
                ['output' => 'PIME packs delivered to CEO', 'activity' => 'Consolidate and deliver performance packs', 'costable_item' => 'Reporting tools', 'planned' => 4, 'planned_unit' => 'packs'],
            ],
            default => [
                ['output' => 'Annual work plan delivered', 'activity' => "Implement approved activities for {$department->dept_name}", 'costable_item' => 'Operational budget', 'planned' => 8, 'planned_unit' => 'milestones'],
                ['output' => 'Service standards met', 'activity' => 'Meet published turnaround standards', 'costable_item' => 'Service tools', 'planned' => 85, 'planned_unit' => '% compliance'],
                ['output' => 'Risks reviewed quarterly', 'activity' => 'Update departmental risk register', 'costable_item' => 'Risk workshops', 'planned' => 4, 'planned_unit' => 'reviews'],
                ['output' => 'Staff capacity actions closed', 'activity' => 'Close training actions linked to gaps', 'costable_item' => 'Training budget', 'planned' => 6, 'planned_unit' => 'actions'],
            ],
        };
    }
}

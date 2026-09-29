<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\Administration\BudgetRequest;
use App\Models\CurriculumVersion;
use App\Models\Finance\FinancePolicy;
use App\Models\Finance\FinancePolicySignoff;
use App\Models\Me\MePolicy;
use App\Models\Me\MePolicySignoff;
use App\Models\Me\MeQuarterlyReport;
use App\Models\ProcurementRequisition;
use App\Models\Qa\QaPlan;
use App\Models\Staff;
use App\Models\Student;
use App\Services\Finance\FinanceDashboardStatsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class CeoDashboardAnalyticsService
{
    public function __construct(
        protected FinanceDashboardStatsService $financeStats,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        return [
            'queues' => $this->queueCounts(),
            'finance' => $this->financeSnapshot(),
            'academics' => $this->academicsSnapshot(),
            'workforce' => $this->workforceSnapshot(),
            'procurement' => $this->procurementSnapshot(),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function queueCounts(): array
    {
        $counts = [
            'budgets' => 0,
            'curriculum' => 0,
            'procurement' => 0,
            'me_reports' => 0,
            'finance_policy' => 0,
            'me_policy' => 0,
            'quality' => 0,
        ];

        if (Schema::hasTable('admin_budget_requests')) {
            $counts['budgets'] = BudgetRequest::query()->where('status', 'executive_review')->count();
        }

        if (Schema::hasTable('curriculum_versions')) {
            $counts['curriculum'] = CurriculumVersion::query()->where('status', 'pending_ceo')->count();
        }

        if (Schema::hasTable('procurement_requisitions')) {
            $counts['procurement'] = ProcurementRequisition::query()->pendingCeoApproval()->count();
        }

        if (Schema::hasTable('me_quarterly_reports')) {
            $counts['me_reports'] = MeQuarterlyReport::query()
                ->where('status', 'ceo_delivered')
                ->whereNull('ceo_reviewed_at')
                ->count();
        }

        if (Schema::hasTable('qa_plans')) {
            $counts['quality'] = QaPlan::query()
                ->where('status', 'compiled')
                ->whereNotNull('compiled_at')
                ->where('compiled_at', '>=', now()->subDays(30))
                ->count();
        }

        $counts['finance_policy'] = $this->financePolicyAwaitingCeoSign() ? 1 : 0;
        $counts['me_policy'] = $this->mePolicyAwaitingCeoSign() ? 1 : 0;

        return $counts;
    }

    public function financePolicyAwaitingCeoSign(): bool
    {
        if (! Schema::hasTable('finance_policies') || ! Schema::hasTable('finance_policy_signoffs')) {
            return false;
        }

        $policy = FinancePolicy::query()->published()->orderByDesc('published_at')->orderByDesc('id')->first();
        if (! $policy) {
            return false;
        }

        return ! FinancePolicySignoff::query()
            ->where('policy_id', $policy->id)
            ->where('signed_role', 'CEO')
            ->exists();
    }

    public function mePolicyAwaitingCeoSign(): bool
    {
        if (! Schema::hasTable('me_policies') || ! Schema::hasTable('me_policy_signoffs')) {
            return false;
        }

        $policy = MePolicy::query()->published()->orderByDesc('published_at')->orderByDesc('id')->first();
        if (! $policy) {
            return false;
        }

        return ! MePolicySignoff::query()
            ->where('policy_id', $policy->id)
            ->where('signed_role', 'CEO')
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private function financeSnapshot(): array
    {
        try {
            $stats = $this->financeStats->stats();
            $reports = $this->financeStats->reportSuite();
            $income = $reports['income_statement'] ?? [];

            return [
                'accounts_receivable' => (float) ($stats['accounts_receivable'] ?? 0),
                'collected_today' => (float) ($stats['collected_today'] ?? 0),
                'open_invoices' => (int) ($stats['open_invoices'] ?? 0),
                'overdue_invoices' => (int) ($stats['overdue_invoices'] ?? 0),
                'treasury_balance' => (float) ($stats['treasury_balance'] ?? 0),
                'revenue' => (float) ($income['revenue'] ?? 0),
                'expenses' => (float) ($income['expenses'] ?? 0),
                'net_income' => (float) ($income['net_income'] ?? 0),
            ];
        } catch (\Throwable) {
            return [
                'accounts_receivable' => 0.0,
                'collected_today' => 0.0,
                'open_invoices' => 0,
                'overdue_invoices' => 0,
                'treasury_balance' => 0.0,
                'revenue' => 0.0,
                'expenses' => 0.0,
                'net_income' => 0.0,
            ];
        }
    }

    /**
     * @return array{total_students: int, active_students: int, programs: int, students_by_program: Collection<int, array{name: string, count: int}>}
     */
    private function academicsSnapshot(): array
    {
        $total = 0;
        $active = 0;
        $programs = 0;
        $byProgram = collect();

        if (Schema::hasTable('students')) {
            $total = Student::query()->count();
            $active = Student::query()
                ->when(
                    Schema::hasColumn('students', 'enrollment_status'),
                    fn ($q) => $q->whereIn('enrollment_status', ['active', 'enrolled', 'continuing']),
                    fn ($q) => $q->where('is_active', true)
                )
                ->count();

            if (Schema::hasTable('academic_programs') && Schema::hasColumn('students', 'program_id')) {
                $byProgram = Student::query()
                    ->selectRaw('program_id, COUNT(*) as student_count')
                    ->whereNotNull('program_id')
                    ->groupBy('program_id')
                    ->orderByDesc('student_count')
                    ->limit(8)
                    ->get()
                    ->map(function ($row) {
                        $program = AcademicProgram::query()->find($row->program_id);

                        return [
                            'name' => $program?->program_name ?? ('Program #'.$row->program_id),
                            'count' => (int) $row->student_count,
                        ];
                    });
            }
        }

        if (Schema::hasTable('academic_programs')) {
            $programs = AcademicProgram::query()->count();
        }

        return [
            'total_students' => $total,
            'active_students' => $active,
            'programs' => $programs,
            'students_by_program' => $byProgram,
        ];
    }

    /**
     * @return array{active_staff: int, total_staff: int}
     */
    private function workforceSnapshot(): array
    {
        if (! Schema::hasTable('staff')) {
            return ['active_staff' => 0, 'total_staff' => 0];
        }

        $query = Staff::query();
        if (method_exists(Staff::class, 'scopeExcludePlatformOperators')) {
            $query->excludePlatformOperators();
        }

        $total = (clone $query)->count();
        $active = (clone $query)->where('employment_status', 'active')->count();

        return [
            'active_staff' => $active,
            'total_staff' => $total,
        ];
    }

    /**
     * @return array{pending_ceo: int, in_pipeline: int}
     */
    private function procurementSnapshot(): array
    {
        if (! Schema::hasTable('procurement_requisitions')) {
            return ['pending_ceo' => 0, 'in_pipeline' => 0];
        }

        return [
            'pending_ceo' => ProcurementRequisition::query()->pendingCeoApproval()->count(),
            'in_pipeline' => ProcurementRequisition::query()
                ->whereIn('status', ['submitted', 'hod_approved', 'finance_approved'])
                ->count(),
        ];
    }
}

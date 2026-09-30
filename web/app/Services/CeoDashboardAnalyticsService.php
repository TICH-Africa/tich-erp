<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\Administration\BudgetRequest;
use App\Models\Applicant;
use App\Models\CurriculumVersion;
use App\Models\Finance\FinancePolicy;
use App\Models\Finance\FinancePolicySignoff;
use App\Models\JobVacancy;
use App\Models\LeaveRequest;
use App\Models\Me\MePolicy;
use App\Models\Me\MePolicySignoff;
use App\Models\Me\MeQuarterlyReport;
use App\Models\PerformanceReview;
use App\Models\ProcurementRequisition;
use App\Models\Qa\IqaAssessment;
use App\Models\RecruitmentApplication;
use App\Models\Staff;
use App\Models\Student;
use App\Services\Administration\AdministrationService;
use App\Services\Finance\FinanceDashboardStatsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CeoDashboardAnalyticsService
{
    public function __construct(
        protected FinanceDashboardStatsService $financeStats,
        protected HrLeaveOverviewService $leaveOverview,
        protected AdministrationService $administration,
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
            'admissions' => $this->admissionsSnapshot(),
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

        if (Schema::hasTable('iqa_assessments')) {
            $counts['quality'] = IqaAssessment::query()
                ->where('status', IqaAssessment::STATUS_PUBLISHED)
                ->whereNotNull('published_at')
                ->where('published_at', '>=', now()->subDays(30))
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
     * @return array<string, mixed>
     */
    private function academicsSnapshot(): array
    {
        $total = 0;
        $active = 0;
        $graduated = 0;
        $withdrawn = 0;
        $deferred = 0;
        $suspended = 0;
        $pending = 0;
        $enrolledThisMonth = 0;
        $enrolledThisYear = 0;
        $programs = 0;
        $byProgram = collect();
        $byStatus = collect();

        if (Schema::hasTable('students')) {
            $total = Student::query()->count();

            if (Schema::hasColumn('students', 'enrollment_status')) {
                $byStatus = Student::query()
                    ->selectRaw('enrollment_status, COUNT(*) as student_count')
                    ->groupBy('enrollment_status')
                    ->pluck('student_count', 'enrollment_status');

                $active = (int) (
                    ($byStatus['active'] ?? 0)
                    + ($byStatus['enrolled'] ?? 0)
                    + ($byStatus['continuing'] ?? 0)
                );
                $graduated = (int) (($byStatus['graduated'] ?? 0) + ($byStatus['alumni'] ?? 0));
                $withdrawn = (int) ($byStatus['withdrawn'] ?? 0);
                $deferred = (int) ($byStatus['deferred'] ?? 0);
                $suspended = (int) ($byStatus['suspended'] ?? 0);
                $pending = (int) ($byStatus['pending'] ?? 0);
            } elseif (Schema::hasColumn('students', 'is_active')) {
                $active = Student::query()->where('is_active', true)->count();
            }

            $admissionColumn = Schema::hasColumn('students', 'date_of_admission')
                ? 'date_of_admission'
                : (Schema::hasColumn('students', 'created_at') ? 'created_at' : null);

            if ($admissionColumn) {
                $enrolledThisMonth = Student::query()
                    ->whereNotNull($admissionColumn)
                    ->where($admissionColumn, '>=', now()->startOfMonth())
                    ->count();
                $enrolledThisYear = Student::query()
                    ->whereNotNull($admissionColumn)
                    ->where($admissionColumn, '>=', now()->startOfYear())
                    ->count();
            }

            if (Schema::hasTable('academic_programs') && Schema::hasColumn('students', 'program_id')) {
                $byProgram = Student::query()
                    ->selectRaw('program_id, COUNT(*) as student_count')
                    ->whereNotNull('program_id')
                    ->groupBy('program_id')
                    ->orderByDesc('student_count')
                    ->limit(10)
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
            'graduated_students' => $graduated,
            'withdrawn_students' => $withdrawn,
            'deferred_students' => $deferred,
            'suspended_students' => $suspended,
            'pending_students' => $pending,
            'enrolled_this_month' => $enrolledThisMonth,
            'enrolled_this_year' => $enrolledThisYear,
            'programs' => $programs,
            'students_by_program' => $byProgram,
            'students_by_status' => $byStatus,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function admissionsSnapshot(): array
    {
        $lifecycle = [
            'submission' => 0,
            'academic_verification' => 0,
            'payment' => 0,
            'admin_approval' => 0,
            'letter_generation' => 0,
            'total' => 0,
        ];

        try {
            $lifecycle = array_merge($lifecycle, $this->administration->admissionsLifecycleStats());
        } catch (\Throwable) {
            // keep zeros
        }

        $rejected = 0;
        $admitted = (int) ($lifecycle['letter_generation'] ?? 0);
        $pendingReview = (int) ($lifecycle['academic_verification'] ?? 0);
        $feePending = (int) ($lifecycle['payment'] ?? 0);

        if (Schema::hasTable('applicants')) {
            $rejected = Applicant::query()->where('status', 'rejected')->count();
            if (Schema::hasColumn('applicants', 'academic_review_status')) {
                $pendingReview = Applicant::query()
                    ->where('status', 'academic_review')
                    ->where('academic_review_status', 'under_review')
                    ->count();
            }
        }

        return [
            'total_applications' => (int) ($lifecycle['total'] ?? 0),
            'new_submissions' => (int) ($lifecycle['submission'] ?? 0),
            'pending_academic_review' => $pendingReview,
            'fee_pending' => $feePending,
            'admitted' => $admitted,
            'rejected' => $rejected,
            'lifecycle' => $lifecycle,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function workforceSnapshot(): array
    {
        $empty = [
            'active_staff' => 0,
            'total_staff' => 0,
            'onboarding' => 0,
            'on_leave_status' => 0,
            'teaching_staff' => 0,
            'non_teaching_staff' => 0,
            'by_status' => collect(),
            'by_department' => collect(),
            'currently_on_leave' => 0,
            'pending_leave' => 0,
            'on_leave_sample' => collect(),
            'open_vacancies' => 0,
            'draft_vacancies' => 0,
            'closed_vacancies' => 0,
            'available_slots' => 0,
            'filled_slots' => 0,
            'open_vacancy_list' => collect(),
            'recruitment_applications' => 0,
            'recruitment_in_pipeline' => 0,
            'performance_total' => 0,
            'performance_awaiting_hr' => 0,
            'performance_completed' => 0,
            'performance_by_rating' => collect(),
        ];

        if (! Schema::hasTable('staff')) {
            return $empty;
        }

        $query = Staff::query();
        if (method_exists(Staff::class, 'scopeExcludePlatformOperators')) {
            $query->excludePlatformOperators();
        }

        $total = (clone $query)->count();
        $active = (clone $query)->where('employment_status', 'active')->count();
        $onboarding = (clone $query)->where('employment_status', 'onboarding')->count();
        $onLeaveStatus = (clone $query)->where('employment_status', 'on_leave')->count();
        $teaching = Schema::hasColumn('staff', 'is_teaching_staff')
            ? (clone $query)->where('is_teaching_staff', 1)->where('employment_status', 'active')->count()
            : 0;
        $nonTeaching = max(0, $active - $teaching);

        $byStatus = (clone $query)
            ->selectRaw('employment_status, COUNT(*) as staff_count')
            ->groupBy('employment_status')
            ->orderByDesc('staff_count')
            ->get()
            ->map(fn ($row) => [
                'status' => (string) ($row->employment_status ?: 'unknown'),
                'count' => (int) $row->staff_count,
            ]);

        $byDepartment = collect();
        if (Schema::hasColumn('staff', 'department_id') && Schema::hasTable('departments')) {
            $byDepartment = (clone $query)
                ->where('employment_status', 'active')
                ->whereNotNull('department_id')
                ->selectRaw('department_id, COUNT(*) as staff_count')
                ->groupBy('department_id')
                ->orderByDesc('staff_count')
                ->limit(8)
                ->get()
                ->map(function ($row) {
                    $name = DB::table('departments')->where('id', $row->department_id)->value('dept_name');

                    return [
                        'name' => $name ?: ('Department #'.$row->department_id),
                        'count' => (int) $row->staff_count,
                    ];
                });
        }

        $currentlyOnLeave = 0;
        $onLeaveSample = collect();
        try {
            $leaveRows = $this->leaveOverview->currentlyOnLeave();
            $currentlyOnLeave = $leaveRows->count();
            $onLeaveSample = $leaveRows->take(8)->map(fn ($row) => [
                'name' => trim(($row->staff?->first_name ?? '').' '.($row->staff?->surname ?? '')) ?: 'Staff',
                'department' => $row->staff?->department?->dept_name ?? '—',
                'leave_type' => $row->leave_type_name,
                'period' => $row->period_label,
            ]);
        } catch (\Throwable) {
            // leave tables may be incomplete
        }

        $pendingLeave = 0;
        if (Schema::hasTable('leave_requests')) {
            $pendingLeave = LeaveRequest::query()
                ->where('overall_status', 'pending_hr')
                ->when(Schema::hasColumn('leave_requests', 'is_cancelled'), fn ($q) => $q->where('is_cancelled', false))
                ->count();
        }

        $vacancyStats = $this->vacancySnapshot();
        $performance = $this->performanceSnapshot();

        return array_merge($empty, [
            'active_staff' => $active,
            'total_staff' => $total,
            'onboarding' => $onboarding,
            'on_leave_status' => $onLeaveStatus,
            'teaching_staff' => $teaching,
            'non_teaching_staff' => $nonTeaching,
            'by_status' => $byStatus,
            'by_department' => $byDepartment,
            'currently_on_leave' => $currentlyOnLeave,
            'pending_leave' => $pendingLeave,
            'on_leave_sample' => $onLeaveSample,
        ], $vacancyStats, $performance);
    }

    /**
     * @return array<string, mixed>
     */
    private function vacancySnapshot(): array
    {
        $result = [
            'open_vacancies' => 0,
            'draft_vacancies' => 0,
            'closed_vacancies' => 0,
            'available_slots' => 0,
            'filled_slots' => 0,
            'open_vacancy_list' => collect(),
            'recruitment_applications' => 0,
            'recruitment_in_pipeline' => 0,
        ];

        if (! Schema::hasTable('job_vacancies')) {
            return $result;
        }

        $openVacancies = JobVacancy::query()
            ->with('department')
            ->where('is_published', 1)
            ->where(function ($q) {
                $q->where('is_closed', 0)->orWhereNull('is_closed');
            })
            ->orderBy('closing_date')
            ->get();

        $result['open_vacancies'] = $openVacancies->count();
        $result['draft_vacancies'] = JobVacancy::query()
            ->where(function ($q) {
                $q->where('is_published', 0)->orWhereNull('is_published');
            })
            ->where(function ($q) {
                $q->where('is_closed', 0)->orWhereNull('is_closed');
            })
            ->count();
        $result['closed_vacancies'] = JobVacancy::query()->where('is_closed', 1)->count();

        $available = 0;
        $filled = 0;
        foreach ($openVacancies as $vacancy) {
            $slots = (int) ($vacancy->slots_available ?? 0);
            $taken = (int) ($vacancy->slots_filled ?? 0);
            $filled += $taken;
            $available += max(0, $slots - $taken);
        }
        $result['available_slots'] = $available;
        $result['filled_slots'] = $filled;

        $result['open_vacancy_list'] = $openVacancies->take(8)->values()->map(fn (JobVacancy $v) => [
            'title' => $v->job_title,
            'department' => $v->department?->dept_name ?? '—',
            'slots_available' => (int) ($v->slots_available ?? 0),
            'slots_open' => max(0, (int) ($v->slots_available ?? 0) - (int) ($v->slots_filled ?? 0)),
            'closing_date' => $v->closing_date?->format('d M Y') ?? '—',
            'employment_type' => $v->employment_type ?? '—',
        ]);

        if (Schema::hasTable('recruitment_applications') && class_exists(RecruitmentApplication::class)) {
            $result['recruitment_applications'] = RecruitmentApplication::query()->count();
            $result['recruitment_in_pipeline'] = RecruitmentApplication::query()
                ->whereIn('status', ['submitted', 'under_review', 'shortlisted'])
                ->count();
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function performanceSnapshot(): array
    {
        $result = [
            'performance_total' => 0,
            'performance_awaiting_hr' => 0,
            'performance_completed' => 0,
            'performance_by_rating' => collect(),
        ];

        if (! Schema::hasTable('performance_reviews')) {
            return $result;
        }

        $result['performance_total'] = PerformanceReview::query()->count();

        if (Schema::hasColumn('performance_reviews', 'hr_approved_at')) {
            $result['performance_awaiting_hr'] = PerformanceReview::query()->whereNull('hr_approved_at')->count();
            $result['performance_completed'] = PerformanceReview::query()->whereNotNull('hr_approved_at')->count();
        }

        if (Schema::hasColumn('performance_reviews', 'overall_rating')) {
            $result['performance_by_rating'] = PerformanceReview::query()
                ->whereNotNull('overall_rating')
                ->selectRaw('overall_rating, COUNT(*) as review_count')
                ->groupBy('overall_rating')
                ->orderByDesc('review_count')
                ->get()
                ->map(fn ($row) => [
                    'rating' => str_replace('_', ' ', (string) $row->overall_rating),
                    'count' => (int) $row->review_count,
                ]);
        }

        return $result;
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

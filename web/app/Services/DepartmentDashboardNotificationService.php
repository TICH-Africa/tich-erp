<?php

namespace App\Services;

use App\Models\Department;
use App\Models\User;
use App\Services\AcademicsAccessService;
use App\Services\Finance\FinanceSidebarNotificationService;
use App\Services\HrSidebarNotificationService;
use App\Services\Qa\QaAssessmentService;
use App\Services\Sidebar\AcademicsSidebarNotificationService;
use App\Services\Sidebar\AdministrationSidebarNotificationService;
use App\Services\Sidebar\IctSidebarNotificationService;
use App\Services\Sidebar\MeSidebarNotificationService;
use App\Services\Sidebar\QaSidebarNotificationService;
use Illuminate\Support\Collection;

class DepartmentDashboardNotificationService
{
    /**
     * Leaf-only keys so rollup parents are not double-counted on the main dashboard.
     *
     * @var array<string, list<string>>
     */
    private const LEAF_KEYS = [
        'HR' => [
            'onboarding',
            'recruitment',
            'leave.requests',
            'documents',
            'offboarding',
            'contracts',
            'profile-changes',
            'attendance',
            'policies',
            'grievances',
            'feedback',
        ],
        'FIN' => [
            'student-finance.adjustments',
            'student-finance.invoices',
            'student-finance.fee-structures',
            'student-finance.installments',
            'student-finance.payments',
            'ap.pending',
            'payroll-runs',
            'payroll-integration',
        ],
        'ADM' => [
            'approvals',
            'applications',
            'lifecycle',
            'statutory',
            'inspection',
        ],
    ];

    /**
     * @var array<string, class-string>
     */
    private const NOTIFICATION_SERVICES = [
        'HR' => HrSidebarNotificationService::class,
        'FIN' => FinanceSidebarNotificationService::class,
        'ADM' => AdministrationSidebarNotificationService::class,
        'ACAD' => AcademicsSidebarNotificationService::class,
        'QA' => QaSidebarNotificationService::class,
        'MNE' => MeSidebarNotificationService::class,
        'ICTO' => IctSidebarNotificationService::class,
        'ICT' => IctSidebarNotificationService::class,
    ];

    /**
     * @param  Collection<int, Department>  $departments
     * @return array<int, int> department id => pending count
     */
    public function countsForDepartments(Collection $departments, ?User $user = null): array
    {
        $counts = [];

        foreach ($departments as $department) {
            $counts[$department->id] = $this->totalCountForDepartment($department, $user);
        }

        return $counts;
    }

    public function totalCountForDepartment(Department $department, ?User $user = null): int
    {
        $code = strtoupper((string) $department->dept_code);
        $qaTasks = $this->qaTaskCountForDepartmentCard($department, $user);

        if ($code === 'ACAD') {
            $notifications = app(AcademicsSidebarNotificationService::class);
            $access = app(AcademicsAccessService::class);

            if ($user && $access->isTeachingOnly($user)) {
                return $qaTasks;
            }

            $counts = $user
                ? $notifications->countsFor($user, $department)
                : $notifications->countsForHub($department);

            $attendance = max(
                (int) ($counts['attendance-ledger.hod'] ?? 0),
                (int) ($counts['attendance-ledger.registrar'] ?? 0)
            );

            return (int) (
                ($counts['applications.pending'] ?? 0)
                + ($counts['units.pending-registry'] ?? 0)
                + ($counts['curriculum.workflow'] ?? 0)
                + ($counts['lesson-plans.review'] ?? 0)
                + $attendance
                + ($counts['special-exam-requests.pending'] ?? 0)
                + ($counts['supplementary-requests.pending'] ?? 0)
                + ($counts['suggestions.open'] ?? 0)
                + ($counts['lifecycle.pending'] ?? 0)
                + $qaTasks
            );
        }

        if (in_array($code, ['QA', 'MNE', 'ICTO', 'ICT'], true)) {
            $serviceClass = self::NOTIFICATION_SERVICES[$code] ?? null;
            if (! $serviceClass) {
                return $qaTasks;
            }

            return app($serviceClass)->dashboardTotal() + $qaTasks;
        }

        $serviceClass = self::NOTIFICATION_SERVICES[$code] ?? null;
        if (! $serviceClass) {
            return $qaTasks;
        }

        $counts = app($serviceClass)->counts();
        $leafKeys = self::LEAF_KEYS[$code] ?? null;

        $moduleTotal = $leafKeys === null
            ? (int) array_sum($counts)
            : (int) collect($leafKeys)->sum(fn (string $key) => (int) ($counts[$key] ?? 0));

        return $moduleTotal + $qaTasks;
    }

    private function qaTaskCountForDepartmentCard(Department $department, ?User $user): int
    {
        if (! $user) {
            return 0;
        }

        $qa = app(QaAssessmentService::class);

        if ($department->isAcademicsHub() || strtoupper((string) $department->dept_code) === 'ACAD') {
            $access = app(AcademicsAccessService::class);
            $learningIds = $access->accessibleLearningDepartmentIds($user, $department);

            return $qa->outstandingTaskCountForUser($user, $learningIds);
        }

        return $qa->outstandingTaskCountForDepartment($user, $department);
    }

    public function formatCount(int $count): ?string
    {
        if ($count <= 0) {
            return null;
        }

        return $count > 99 ? '99+' : (string) $count;
    }
}

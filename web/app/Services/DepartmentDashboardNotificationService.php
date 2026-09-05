<?php

namespace App\Services;

use App\Models\Department;
use App\Models\User;
use App\Services\Finance\FinanceSidebarNotificationService;
use App\Services\HrSidebarNotificationService;
use App\Services\Sidebar\AcademicsSidebarNotificationService;
use App\Services\Sidebar\AdministrationSidebarNotificationService;
use Illuminate\Support\Collection;

class DepartmentDashboardNotificationService
{
    /**
     * Department codes mapped to sidebar notification services (institutional queue totals).
     *
     * @var array<string, class-string>
     */
    private const NOTIFICATION_SERVICES = [
        'HR' => HrSidebarNotificationService::class,
        'FIN' => FinanceSidebarNotificationService::class,
        'ADM' => AdministrationSidebarNotificationService::class,
        'ACAD' => AcademicsSidebarNotificationService::class,
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
        $serviceClass = self::NOTIFICATION_SERVICES[$department->dept_code] ?? null;

        if (! $serviceClass) {
            return 0;
        }

        if ($department->dept_code === 'ACAD') {
            $notifications = app(AcademicsSidebarNotificationService::class);
            $access = app(AcademicsAccessService::class);

            if ($user && $access->isTeachingOnly($user)) {
                return 0;
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
            );
        }

        $counts = app($serviceClass)->counts();

        return (int) array_sum($counts);
    }

    public function formatCount(int $count): ?string
    {
        if ($count <= 0) {
            return null;
        }

        return $count > 99 ? '99+' : (string) $count;
    }
}

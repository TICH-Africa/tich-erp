<?php

namespace App\Services;

use App\Models\Department;
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
    public function countsForDepartments(Collection $departments): array
    {
        $counts = [];

        foreach ($departments as $department) {
            $counts[$department->id] = $this->totalCountForDepartment($department);
        }

        return $counts;
    }

    public function totalCountForDepartment(Department $department): int
    {
        $serviceClass = self::NOTIFICATION_SERVICES[$department->dept_code] ?? null;

        if (! $serviceClass) {
            return 0;
        }

        if ($department->dept_code === 'ACAD') {
            $counts = app(AcademicsSidebarNotificationService::class)->countsForHub($department);

            return (int) array_sum($counts);
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

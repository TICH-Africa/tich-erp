<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Services\DepartmentDashboardNotificationService;
use App\Services\DepartmentDashboardService;
use App\Services\EmployeeAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        DepartmentDashboardService $departmentDashboard,
        DepartmentDashboardNotificationService $departmentNotifications,
        EmployeeAssignmentService $employeeAssignment,
        AuthService $authService,
    ): View|RedirectResponse {
        $user = auth()->user();

        if ($authService->isEnrolledStudent($user)) {
            return redirect()->route('portal.dashboard');
        }

        $departments = $departmentDashboard->mainDepartmentsForUser($user);
        $awaitingAssignment = $employeeAssignment->isAwaitingDepartmentAssignment($user);

        return view('dashboard', [
            'departments' => $departments,
            'departmentNotificationCounts' => $departmentNotifications->countsForDepartments($departments),
            'formatNotificationCount' => fn (int $count) => $departmentNotifications->formatCount($count),
            'awaitingDepartmentAssignment' => $awaitingAssignment,
            'cardDescription' => fn ($department) => $departmentDashboard->cardDescription($department),
            'categoryLabel' => fn ($department) => $departmentDashboard->categoryLabel($department),
            'entryUrl' => fn ($department) => $departmentDashboard->entryUrlForDepartment($user, $department),
            'cardActionLabel' => $departmentDashboard->cardActionLabel($user),
            'isSuperAdmin' => app(\App\Services\RBACService::class)->isPlatformAdministrator($user),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Services\DepartmentDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DepartmentDashboardService $departmentDashboard, AuthService $authService): View|RedirectResponse
    {
        $user = auth()->user();

        if ($authService->isEnrolledStudent($user)) {
            return redirect()->route('portal.dashboard');
        }

        return view('dashboard', [
            'departments' => $departmentDashboard->mainDepartmentsForUser($user),
            'cardDescription' => fn ($department) => $departmentDashboard->cardDescription($department),
            'categoryLabel' => fn ($department) => $departmentDashboard->categoryLabel($department),
            'entryUrl' => fn ($department) => $departmentDashboard->entryUrlForDepartment($user, $department),
            'isSuperAdmin' => $user->hasRole('Super Admin'),
        ]);
    }
}

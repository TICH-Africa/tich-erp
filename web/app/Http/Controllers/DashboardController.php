<?php

namespace App\Http\Controllers;

use App\Services\DepartmentDashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DepartmentDashboardService $departmentDashboard): View
    {
        $user = auth()->user();

        return view('dashboard', [
            'departments' => $departmentDashboard->mainDepartmentsForUser($user),
            'cardDescription' => fn ($department) => $departmentDashboard->cardDescription($department),
            'categoryLabel' => fn ($department) => $departmentDashboard->categoryLabel($department),
            'isSuperAdmin' => $user->hasRole('Super Admin'),
        ]);
    }
}

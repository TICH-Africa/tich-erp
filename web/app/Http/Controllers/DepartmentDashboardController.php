<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Services\DepartmentDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DepartmentDashboardController extends Controller
{
    public function show(Request $request, Department $department, DepartmentDashboardService $departmentDashboard): View
    {
        if (! $department->is_active) {
            throw new NotFoundHttpException();
        }

        $user = $request->user();

        if (! $departmentDashboard->userCanAccessDepartment($user, $department)) {
            abort(403, 'You do not have access to this department.');
        }

        $section = $departmentDashboard->resolveSection($request, $user, $department);
        $department->load(['group', 'campus', 'parent']);

        return view('departments.show', [
            'department' => $department,
            'academicsHub' => $department->isLearningDepartment() ? $department->academicsHub() : null,
            'section' => $section,
            'childDepartments' => $departmentDashboard->accessibleChildDepartments($user, $department),
            'modules' => $departmentDashboard->modulesForDepartment($user, $department),
            'sidebarNavigation' => $departmentDashboard->sidebarNavigation($user, $department),
            'dashboardViewType' => $departmentDashboard->dashboardViewType($user, $department),
            'overviewStats' => $departmentDashboard->overviewStats($user, $department),
            'categoryLabel' => fn (Department $dept) => $departmentDashboard->categoryLabel($dept),
            'cardDescription' => fn (Department $dept) => $departmentDashboard->cardDescription(
                $dept->loadCount(['children' => fn ($query) => $query->where('is_active', true)])
            ),
        ]);
    }
}

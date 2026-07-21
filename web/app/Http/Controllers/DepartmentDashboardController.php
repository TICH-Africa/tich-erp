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

        return view('departments.show', [
            'department' => $department->load(['group', 'campus']),
            'childDepartments' => $departmentDashboard->accessibleChildDepartments($user, $department),
            'modules' => $departmentDashboard->modulesForDepartment($user, $department),
            'categoryLabel' => fn (Department $dept) => $departmentDashboard->categoryLabel($dept),
            'cardDescription' => fn (Department $dept) => $departmentDashboard->cardDescription(
                $dept->loadCount(['children' => fn ($query) => $query->where('is_active', true)])
            ),
        ]);
    }
}

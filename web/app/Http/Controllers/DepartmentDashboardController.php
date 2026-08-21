<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Services\DepartmentBudgetingService;
use App\Services\DepartmentDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DepartmentDashboardController extends Controller
{
    public function show(
        Request $request,
        Department $department,
        DepartmentDashboardService $departmentDashboard,
        DepartmentBudgetingService $budgeting,
    ): RedirectResponse {
        if (! $department->is_active) {
            throw new NotFoundHttpException();
        }

        $user = $request->user();

        if (! $departmentDashboard->userCanAccessDepartment($user, $department)) {
            abort(403, 'You do not have access to this department.');
        }

        $moduleHome = $budgeting->moduleHomeUrlForDepartment($department);
        if ($moduleHome) {
            return redirect()->to($moduleHome);
        }

        return redirect()
            ->route('dashboard')
            ->with('status', 'Open this area from its module dashboard instead of the legacy department hub.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Department;
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
    ): RedirectResponse {
        if (! $department->is_active) {
            throw new NotFoundHttpException();
        }

        $user = $request->user();

        if (! $departmentDashboard->userCanAccessDepartment($user, $department)) {
            abort(403, 'You do not have access to this department.');
        }

        $entryUrl = $departmentDashboard->entryUrlForDepartment($user, $department);

        if ($entryUrl === route('dashboard')) {
            return redirect()
                ->route('dashboard')
                ->with('status', 'Open this area from its module dashboard instead of the legacy department hub.');
        }

        return redirect()->to($entryUrl);
    }
}

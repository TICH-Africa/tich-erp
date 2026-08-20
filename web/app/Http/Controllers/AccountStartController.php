<?php

namespace App\Http\Controllers;

use App\Services\EmployeePortalService;
use App\Services\EmployeeProfileCompletenessService;
use App\Services\RBACService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountStartController extends Controller
{
    public function __invoke(
        Request $request,
        EmployeePortalService $employeePortal,
        EmployeeProfileCompletenessService $completeness,
        RBACService $rbac,
    ): View {
        $user = $request->user();
        $staff = $employeePortal->staffForUser($user);
        $mustComplete = $staff && ! $completeness->isComplete($staff);

        return view('account.start', [
            'user' => $user,
            'staff' => $staff,
            'mustCompleteProfile' => $mustComplete,
            'missingProfileLabels' => $staff ? $completeness->missingLabels($staff) : [],
            'canOpenDashboard' => $rbac->hasPermission($user, 'dashboard.access'),
            'canOpenEmployeePortal' => $staff !== null,
        ]);
    }
}

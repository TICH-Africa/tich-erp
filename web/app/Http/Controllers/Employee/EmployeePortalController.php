<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\EmployeePortalService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeePortalController extends Controller
{
    public function __construct(
        protected EmployeePortalService $employeePortal,
    ) {}

    public function __invoke(Request $request): View
    {
        $staff = $request->attributes->get('portal_staff')
            ?? $this->employeePortal->staffForUser($request->user());

        abort_unless($staff, 403);

        $portalData = $this->employeePortal->dashboardData($staff);

        return view('employee.dashboard', array_merge($portalData, [
            'portalTitle' => 'My Employee Portal',
        ]));
    }
}

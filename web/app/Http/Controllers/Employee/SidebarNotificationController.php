<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\EmployeePortalService;
use App\Services\Sidebar\EmployeeSidebarNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SidebarNotificationController extends Controller
{
    public function __invoke(
        Request $request,
        EmployeeSidebarNotificationService $notifications,
        EmployeePortalService $employeePortal,
    ): JsonResponse {
        $staff = $employeePortal->staffForUser($request->user());
        abort_unless($staff, 403);

        return response()->json([
            'counts' => $notifications->countsFor($staff, true),
            'labels' => $notifications->formattedCountsFor($staff, true),
        ]);
    }
}

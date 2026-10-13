<?php

namespace App\Http\Controllers\Api\Employee\mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Employee\mobile\Concerns\ResolvesPortalStaff;
use App\Services\Sidebar\EmployeeSidebarNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ResolvesPortalStaff;

    public function __invoke(
        Request $request,
        EmployeeSidebarNotificationService $notifications,
    ): JsonResponse {
        $staff = $this->portalStaff($request);

        return response()->json([
            'counts' => $notifications->countsFor($staff, true, $request->user()),
            'labels' => $notifications->formattedCountsFor($staff, true, $request->user()),
        ]);
    }
}

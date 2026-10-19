<?php

namespace App\Http\Controllers\Api\Staff\mobile;

use App\Http\Controllers\Api\Staff\mobile\Concerns\ResolvesPortalTeachingStaff;
use App\Http\Controllers\Controller;
use App\Services\Sidebar\StaffSidebarNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ResolvesPortalTeachingStaff;

    public function __invoke(
        Request $request,
        StaffSidebarNotificationService $notifications,
    ): JsonResponse {
        $staff = $this->portalStaff($request);
        $user = $request->user();

        $counts = $notifications->countsFor($staff, $user, true);

        return response()->json([
            'counts' => $counts,
            'labels' => $notifications->formattedCounts($counts),
        ]);
    }
}

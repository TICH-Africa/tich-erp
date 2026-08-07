<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\Sidebar\StaffSidebarNotificationService;
use App\Services\StaffPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SidebarNotificationController extends Controller
{
    public function __invoke(
        Request $request,
        StaffSidebarNotificationService $notifications,
        StaffPortalService $staffPortal,
    ): JsonResponse {
        $user = $request->user();
        $staff = $staffPortal->staffForUser($user);
        abort_unless($staff, 403);

        return response()->json([
            'counts' => $notifications->countsFor($staff, $user, true),
            'labels' => $notifications->formattedCountsFor($staff, $user, true),
        ]);
    }
}

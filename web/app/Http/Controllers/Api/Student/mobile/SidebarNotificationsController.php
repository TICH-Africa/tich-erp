<?php

namespace App\Http\Controllers\Api\Student\mobile;

use App\Http\Controllers\Api\Student\mobile\Concerns\ResolvesPortalStudent;
use App\Http\Controllers\Controller;
use App\Services\Sidebar\StudentSidebarNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SidebarNotificationsController extends Controller
{
    use ResolvesPortalStudent;

    public function __invoke(
        Request $request,
        StudentSidebarNotificationService $notifications,
    ): JsonResponse {
        $student = $this->portalStudent($request);
        $counts = $notifications->countsFor($student, true);

        return response()->json([
            'counts' => $counts,
            'labels' => $notifications->formattedCounts($counts),
        ]);
    }
}

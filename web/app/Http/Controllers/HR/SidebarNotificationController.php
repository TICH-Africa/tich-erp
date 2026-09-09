<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Services\HrSidebarNotificationService;
use App\Support\QaTaskSidebarBadge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SidebarNotificationController extends Controller
{
    public function __invoke(Request $request, HrSidebarNotificationService $notifications): JsonResponse
    {
        $counts = $notifications->counts(true);
        $labels = $notifications->formattedCounts(true);
        [$counts, $labels] = QaTaskSidebarBadge::merge($counts, $labels, $request->user());

        return response()->json([
            'counts' => $counts,
            'labels' => $labels,
        ]);
    }
}

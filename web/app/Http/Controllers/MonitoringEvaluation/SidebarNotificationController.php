<?php

namespace App\Http\Controllers\MonitoringEvaluation;

use App\Http\Controllers\Controller;
use App\Services\Sidebar\MeSidebarNotificationService;
use App\Support\QaTaskSidebarBadge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SidebarNotificationController extends Controller
{
    public function __invoke(Request $request, MeSidebarNotificationService $notifications): JsonResponse
    {
        $counts = $notifications->counts($request->user(), true);
        $labels = $notifications->labels($request->user(), true);
        [$counts, $labels] = QaTaskSidebarBadge::merge($counts, $labels, $request->user());

        return response()->json([
            'counts' => $counts,
            'labels' => $labels,
        ]);
    }
}

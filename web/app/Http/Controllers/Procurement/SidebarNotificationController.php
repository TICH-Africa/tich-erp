<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Services\Procurement\ProcurementSidebarNotificationService;
use App\Support\QaTaskSidebarBadge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SidebarNotificationController extends Controller
{
    public function __invoke(Request $request, ProcurementSidebarNotificationService $notifications): JsonResponse
    {
        $counts = $notifications->counts(true);
        $labels = $notifications->formattedCounts(true);
        [$counts, $labels] = QaTaskSidebarBadge::merge($counts, $labels, $request->user(), 'procurement');

        return response()->json([
            'counts' => $counts,
            'labels' => $labels,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\FinanceSidebarNotificationService;
use App\Support\QaTaskSidebarBadge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SidebarNotificationController extends Controller
{
    public function __invoke(Request $request, FinanceSidebarNotificationService $notifications): JsonResponse
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

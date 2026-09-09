<?php

namespace App\Http\Controllers\Ict;

use App\Http\Controllers\Controller;
use App\Services\Sidebar\IctSidebarNotificationService;
use App\Support\QaTaskSidebarBadge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SidebarNotificationController extends Controller
{
    public function __invoke(Request $request, IctSidebarNotificationService $notifications): JsonResponse
    {
        $counts = $notifications->counts(true);
        $labels = $notifications->labels(true);
        [$counts, $labels] = QaTaskSidebarBadge::merge($counts, $labels, $request->user());

        return response()->json([
            'counts' => $counts,
            'labels' => $labels,
        ]);
    }
}

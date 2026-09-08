<?php

namespace App\Http\Controllers\MonitoringEvaluation;

use App\Http\Controllers\Controller;
use App\Services\Sidebar\MeSidebarNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SidebarNotificationController extends Controller
{
    public function __invoke(Request $request, MeSidebarNotificationService $notifications): JsonResponse
    {
        return response()->json([
            'counts' => $notifications->counts($request->user(), true),
            'labels' => $notifications->labels($request->user(), true),
        ]);
    }
}

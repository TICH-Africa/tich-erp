<?php

namespace App\Http\Controllers\Qa;

use App\Http\Controllers\Controller;
use App\Services\Sidebar\QaSidebarNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SidebarNotificationController extends Controller
{
    public function __invoke(Request $request, QaSidebarNotificationService $notifications): JsonResponse
    {
        return response()->json([
            'counts' => $notifications->counts($request->user(), true),
            'labels' => $notifications->labels($request->user(), true),
        ]);
    }
}

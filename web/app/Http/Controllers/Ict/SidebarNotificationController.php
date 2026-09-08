<?php

namespace App\Http\Controllers\Ict;

use App\Http\Controllers\Controller;
use App\Services\Sidebar\IctSidebarNotificationService;
use Illuminate\Http\JsonResponse;

class SidebarNotificationController extends Controller
{
    public function __invoke(IctSidebarNotificationService $notifications): JsonResponse
    {
        return response()->json([
            'counts' => $notifications->counts(true),
            'labels' => $notifications->labels(true),
        ]);
    }
}

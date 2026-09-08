<?php

namespace App\Http\Controllers\Ceo;

use App\Http\Controllers\Controller;
use App\Services\Sidebar\CeoSidebarNotificationService;
use Illuminate\Http\JsonResponse;

class SidebarNotificationController extends Controller
{
    public function __invoke(CeoSidebarNotificationService $notifications): JsonResponse
    {
        return response()->json([
            'counts' => $notifications->counts(true),
            'labels' => $notifications->labels(true),
        ]);
    }
}

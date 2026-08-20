<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Services\Sidebar\AdministrationSidebarNotificationService;
use Illuminate\Http\JsonResponse;

class SidebarNotificationController extends Controller
{
    public function __invoke(AdministrationSidebarNotificationService $notifications): JsonResponse
    {
        return response()->json([
            'counts' => $notifications->counts(true),
            'labels' => $notifications->formattedCounts(true),
        ]);
    }
}

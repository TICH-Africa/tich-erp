<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Sidebar\AdminSidebarNotificationService;
use Illuminate\Http\JsonResponse;

class SidebarNotificationController extends Controller
{
    public function __invoke(AdminSidebarNotificationService $notifications): JsonResponse
    {
        return response()->json([
            'counts' => $notifications->counts(true),
            'labels' => $notifications->formattedCounts(true),
        ]);
    }
}

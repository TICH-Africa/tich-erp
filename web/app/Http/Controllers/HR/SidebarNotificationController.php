<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Services\HrSidebarNotificationService;
use Illuminate\Http\JsonResponse;

class SidebarNotificationController extends Controller
{
    public function __invoke(HrSidebarNotificationService $notifications): JsonResponse
    {
        return response()->json([
            'counts' => $notifications->counts(true),
            'labels' => $notifications->formattedCounts(true),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\FinanceSidebarNotificationService;
use Illuminate\Http\JsonResponse;

class SidebarNotificationController extends Controller
{
    public function __invoke(FinanceSidebarNotificationService $notifications): JsonResponse
    {
        return response()->json([
            'counts' => $notifications->counts(true),
            'labels' => $notifications->formattedCounts(true),
        ]);
    }
}

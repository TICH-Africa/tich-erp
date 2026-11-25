<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Finance\StudentFeeBalanceService;
use App\Services\Sidebar\StudentSidebarNotificationService;
use App\Services\StudentPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SidebarNotificationController extends Controller
{
    public function __invoke(
        Request $request,
        StudentSidebarNotificationService $notifications,
        StudentPortalService $studentPortal,
        StudentFeeBalanceService $feeBalances,
    ): JsonResponse {
        $student = $studentPortal->studentForUser($request->user());
        abort_unless($student, 403);

        return response()->json([
            'counts' => $notifications->countsFor($student, true),
            'labels' => $notifications->formattedCountsFor($student, true),
            'finance_summary' => $feeBalances->portalSummary($student),
        ]);
    }
}

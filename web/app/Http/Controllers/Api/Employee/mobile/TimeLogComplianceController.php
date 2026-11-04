<?php

namespace App\Http\Controllers\Api\Employee\mobile;

use App\Http\Controllers\Api\Employee\mobile\Concerns\ResolvesPortalStaff;
use App\Http\Controllers\Controller;
use App\Services\WeeklyTimeLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimeLogComplianceController extends Controller
{
    use ResolvesPortalStaff;

    public function __invoke(Request $request, WeeklyTimeLogService $timeLogs): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $hold = $timeLogs->portalComplianceHold($staff);

        return response()->json([
            'blocked' => $hold !== null,
            'hold' => $hold,
        ]);
    }
}

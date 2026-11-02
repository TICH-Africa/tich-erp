<?php

namespace App\Http\Controllers\Api\Procurement\mobile;

use App\Http\Controllers\Controller;
use App\Services\Procurement\ProcurementMobilePortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __construct(
        protected ProcurementMobilePortalService $portal,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $payload = Cache::remember('procurement.mobile.dashboard.v1', 20, fn () => $this->portal->dashboard($user));

        return response()->json($payload);
    }
}

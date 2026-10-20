<?php

namespace App\Http\Controllers\Api\Procurement\mobile;

use App\Http\Controllers\Controller;
use App\Services\Procurement\ProcurementMobilePortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected ProcurementMobilePortalService $portal,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        return response()->json($this->portal->dashboard($request->user()));
    }
}

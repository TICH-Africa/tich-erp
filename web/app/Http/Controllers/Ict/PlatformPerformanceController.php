<?php

namespace App\Http\Controllers\Ict;

use App\Http\Controllers\Controller;
use App\Services\Ict\PlatformPerformanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PlatformPerformanceController extends Controller
{
    public function __construct(
        protected PlatformPerformanceService $performance,
    ) {}

    public function index(): View
    {
        return view('ict.platform-performance.index', [
            'initialMetrics' => $this->performance->snapshot(),
            'metricsUrl' => route('ict.platform-performance.metrics'),
        ]);
    }

    public function metrics(): JsonResponse
    {
        return response()->json($this->performance->snapshot());
    }
}

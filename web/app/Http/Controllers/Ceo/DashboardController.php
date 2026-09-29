<?php

namespace App\Http\Controllers\Ceo;

use App\Http\Controllers\Controller;
use App\Services\CeoDashboardAnalyticsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected CeoDashboardAnalyticsService $analytics,
    ) {}

    public function __invoke(): View
    {
        $overview = $this->analytics->overview();

        return view('ceo.dashboard', [
            'queues' => $overview['queues'],
            'finance' => $overview['finance'],
            'academics' => $overview['academics'],
            'workforce' => $overview['workforce'],
            'procurement' => $overview['procurement'],
            'pendingBudgets' => $overview['queues']['budgets'] ?? 0,
            'pendingCurriculum' => $overview['queues']['curriculum'] ?? 0,
            'pendingProcurement' => $overview['queues']['procurement'] ?? 0,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\FinanceDashboardStatsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected FinanceDashboardStatsService $stats,
    ) {}

    public function __invoke(): View
    {
        return view('finance.dashboard', [
            'stats' => $this->stats->stats(),
        ]);
    }
}

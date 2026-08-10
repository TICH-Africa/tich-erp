<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\FinanceDashboardStatsService;
use App\Services\Finance\LedgerService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LedgerController extends Controller
{
    public function __construct(
        protected LedgerService $ledger,
        protected FinanceDashboardStatsService $stats,
    ) {}

    public function index(): View
    {
        return view('finance.ledger.index', [
            'entries' => $this->ledger->recentEntries(100),
            'balances' => $this->ledger->accountBalances(),
            'mainAccount' => config('finance.main_treasury_account'),
        ]);
    }

    public function reports(Request $request): View
    {
        $report = $request->string('report')->toString() ?: 'trial_balance';
        $suite = $this->stats->reportSuite();

        return view('finance.ledger.reports', [
            'report' => $report,
            'suite' => $suite,
        ]);
    }
}

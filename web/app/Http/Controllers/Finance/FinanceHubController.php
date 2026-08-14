<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\FinanceNavigationService;
use App\Services\FinanceDashboardStatsService;
use Illuminate\View\View;

class FinanceHubController extends Controller
{
    public function __construct(
        protected FinanceNavigationService $navigation,
        protected FinanceDashboardStatsService $stats,
    ) {}

    public function studentFinance(): View
    {
        $dept = $this->navigation->departmentParams();

        abort_if($dept === [], 404, 'Finance department is not configured.');

        $chartData = $this->stats->studentFinanceChartData();

        \Log::debug('FinanceHubController@studentFinance chartData', [
            'chartData' => $chartData,
            'invoice_count' => \App\Models\Finance\Invoice::count(),
            'payment_count' => \App\Models\Finance\Payment::count(),
            'account_count' => \App\Models\Finance\StudentAccount::count(),
        ]);

        return view('finance.student-finance.index', [
            'department' => $this->navigation->financeDepartment(),
            'chartData' => $chartData,
        ]);
    }

    public function records(): View
    {
        return view('finance.records.index', [
            'departmentParams' => $this->navigation->departmentParams(),
            'chartData' => $this->stats->financeRecordsChartData(),
        ]);
    }

    public function employee(): View
    {
        return view('finance.employee.index', [
            'departmentParams' => $this->navigation->departmentParams(),
            'chartData' => $this->stats->employeeFinanceChartData(),
        ]);
    }
}

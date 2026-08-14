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

        return view('finance.student-finance.index', [
            'department' => $this->navigation->financeDepartment(),
            'chartData' => $this->stats->studentFinanceChartData(),
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

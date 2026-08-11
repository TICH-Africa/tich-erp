<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\FinanceNavigationService;
use Illuminate\View\View;

class FinanceHubController extends Controller
{
    public function __construct(
        protected FinanceNavigationService $navigation,
    ) {}

    public function studentFinance(): View
    {
        $dept = $this->navigation->departmentParams();

        abort_if($dept === [], 404, 'Finance department is not configured.');

        return view('finance.student-finance.index', [
            'department' => $this->navigation->financeDepartment(),
        ]);
    }

    public function records(): View
    {
        return view('finance.records.index', [
            'departmentParams' => $this->navigation->departmentParams(),
        ]);
    }

    public function employee(): View
    {
        return view('finance.employee.index', [
            'departmentParams' => $this->navigation->departmentParams(),
        ]);
    }
}

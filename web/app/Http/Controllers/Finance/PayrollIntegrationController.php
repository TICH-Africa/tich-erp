<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\PayrollRun;
use App\Services\Finance\PayrollRunService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollIntegrationController extends Controller
{
    public function __construct(
        protected PayrollRunService $payrollRuns,
    ) {}

    public function index(Request $request, Department $department): View
    {
        $runs = PayrollRun::query()
            ->with(['creator', 'approver', 'poster'])
            ->whereIn('status', [PayrollRun::STATUS_APPROVED, PayrollRun::STATUS_POSTED])
            ->orderByDesc('pay_period_year')
            ->orderByDesc('pay_period_month')
            ->paginate(20);

        return view('finance.payroll-integration.index', [
            'department' => $department,
            'runs' => $runs,
            'departmentParams' => ['department' => $department->id],
        ]);
    }

    public function show(Request $request, PayrollRun $payrollRun, Department $department): View
    {
        $payrollRun->load(['items.staff', 'creator', 'approver', 'poster']);

        return view('finance.payroll-integration.show', [
            'department' => $department,
            'run' => $payrollRun,
            'departmentParams' => ['department' => $department->id],
        ]);
    }

    public function post(Request $request, PayrollRun $payrollRun, Department $department): RedirectResponse
    {
        $staffId = (int) ($request->user()->staff_id ?? \App\Models\Staff::query()->value('id') ?? 1);

        $this->payrollRuns->postToGeneralLedger($payrollRun, $staffId);

        return redirect()
            ->route('finance.payroll-integration.show', ['payrollRun' => $payrollRun->id])
            ->with('success', "Payroll run {$payrollRun->run_number} posted to the general ledger.");
    }
}

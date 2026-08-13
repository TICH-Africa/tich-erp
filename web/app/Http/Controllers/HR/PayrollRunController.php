<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Services\Finance\PayrollRunService;
use App\Services\PrintDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollRunController extends Controller
{
    public function __construct(
        protected PayrollRunService $payrollRuns,
        protected PrintDocumentService $printDocuments,
    ) {}

    protected function viewPrefix(): string
    {
        return 'hr.payroll.runs';
    }

    protected function routePrefix(): string
    {
        return 'hr.payroll.runs';
    }

    protected function printView(): string
    {
        return 'hr.payroll.print';
    }

    public function index(Request $request): View
    {
        $runs = PayrollRun::query()
            ->with(['creator', 'approver', 'poster'])
            ->orderByDesc('pay_period_year')
            ->orderByDesc('pay_period_month')
            ->paginate(20);

        return view($this->viewPrefix().'.index', [
            'runs' => $runs,
        ]);
    }

    public function create(): View
    {
        $defaultPeriod = now()->subMonth();

        return view($this->viewPrefix().'.create', [
            'defaultYear' => (int) $defaultPeriod->year,
            'defaultMonth' => (int) $defaultPeriod->month,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pay_period_year' => 'required|integer|min:2020|max:2100',
            'pay_period_month' => 'required|integer|min:1|max:12',
            'notes' => 'nullable|string|max:1000',
        ]);

        $staffId = (int) ($request->user()->staff_id ?? \App\Models\Staff::query()->value('id') ?? 1);

        $run = $this->payrollRuns->createRun(
            (int) $validated['pay_period_year'],
            (int) $validated['pay_period_month'],
            $staffId,
            $validated['notes'] ?? null,
        );

        return redirect()
            ->route($this->routePrefix().'.show', $run)
            ->with('success', "Payroll run {$run->run_number} created with {$run->staff_count} staff.");
    }

    public function show(PayrollRun $payrollRun): View
    {
        $payrollRun->load(['items.staff.department', 'items.statutoryDeductions', 'creator', 'approver', 'poster']);

        return view($this->viewPrefix().'.show', [
            'run' => $payrollRun,
        ]);
    }

    public function recalculate(PayrollRun $payrollRun): RedirectResponse
    {
        $this->payrollRuns->populateRun($payrollRun);

        return back()->with('success', 'Payroll run recalculated from current staff records.');
    }

    public function approve(Request $request, PayrollRun $payrollRun): RedirectResponse
    {
        $staffId = (int) ($request->user()->staff_id ?? \App\Models\Staff::query()->value('id') ?? 1);

        $this->payrollRuns->approve($payrollRun, $staffId);

        return back()->with('success', 'Payroll run approved. Payslips are ready for distribution.');
    }

    public function cancel(PayrollRun $payrollRun): RedirectResponse
    {
        $this->payrollRuns->cancel($payrollRun);

        return redirect()
            ->route($this->routePrefix().'.index')
            ->with('success', 'Payroll run cancelled.');
    }

    public function itemPayslip(PayrollItem $payrollItem): View
    {
        $breakdown = $payrollItem->breakdown();
        abort_if($breakdown === [], 404);

        $run = $payrollItem->run;

        return $this->printDocuments->render($this->printView(), [
            'documentTitle' => ($breakdown['payroll_scheme'] ?? 'employee') === 'withholding'
                ? 'Consultant Payment Statement'
                : 'Monthly Payslip',
            'documentSubtitle' => ($breakdown['employee_name'] ?? 'Staff member').' · '.$run?->periodLabel(),
            'documentRef' => $this->printDocuments->documentRef('PAY', $payrollItem->payslip_number),
            'metaRows' => [],
            'breakdown' => $breakdown,
            'payPeriod' => $run?->periodLabel() ?? now()->format('F Y'),
            'hideActions' => false,
            'backUrl' => route($this->routePrefix().'.show', $payrollItem->payroll_run_id),
            'pdfUrl' => route($this->routePrefix().'.item.payslip.pdf', $payrollItem),
            'bodyClass' => 'tich-payslip-page',
        ]);
    }

    public function itemPayslipPdf(PayrollItem $payrollItem): StreamedResponse
    {
        $breakdown = $payrollItem->breakdown();
        abort_if($breakdown === [], 404);

        $run = $payrollItem->run;
        $slug = Str::slug($breakdown['employee_name'] ?? 'payslip');

        return $this->printDocuments->downloadPdf(
            $this->printView(),
            [
                'documentTitle' => 'Monthly Payslip',
                'documentSubtitle' => ($breakdown['employee_name'] ?? 'Staff member').' · '.$run?->periodLabel(),
                'documentRef' => $this->printDocuments->documentRef('PAY', $payrollItem->payslip_number),
                'metaRows' => [],
                'breakdown' => $breakdown,
                'payPeriod' => $run?->periodLabel() ?? now()->format('F Y'),
                'hideActions' => true,
                'bodyClass' => 'tich-payslip-page',
            ],
            'payslip-'.$slug.'.pdf',
        );
    }

    public function exportStatutory(PayrollRun $payrollRun, string $agency): Response
    {
        abort_unless(in_array($agency, ['kra', 'nssf', 'sha'], true), 404);

        $report = $this->payrollRuns->statutoryReport($payrollRun, $agency);
        $filename = strtolower($agency).'-'.$payrollRun->run_number.'.csv';

        return response()->streamDownload(function () use ($report) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Employee No',
                'Employee Name',
                'KRA PIN',
                'NSSF No',
                'SHA No',
                'Deduction Type',
                'Gross (KES)',
                'Rate (%)',
                'Employee (KES)',
                'Employer (KES)',
                'Total (KES)',
            ]);

            foreach ($report['rows'] as $row) {
                fputcsv($handle, [
                    $row['employee_number'],
                    $row['employee_name'],
                    $row['kra_pin'],
                    $row['nssf_number'],
                    $row['sha_number'],
                    $row['deduction_type'],
                    number_format($row['gross_salary'], 2, '.', ''),
                    $row['rate'] !== null ? number_format((float) $row['rate'], 2, '.', '') : '',
                    number_format($row['employee_amount'], 2, '.', ''),
                    number_format($row['employer_amount'], 2, '.', ''),
                    number_format($row['total_amount'], 2, '.', ''),
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['', '', '', '', '', 'TOTALS', '', '',
                number_format($report['totals']['employee'], 2, '.', ''),
                number_format($report['totals']['employer'], 2, '.', ''),
                number_format($report['totals']['combined'], 2, '.', ''),
            ]);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}

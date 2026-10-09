<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\PayrollItem;
use App\Services\EmployeePortalService;
use App\Services\PrintDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeePayslipController extends Controller
{
    public function __construct(
        protected EmployeePortalService $employeePortal,
        protected PrintDocumentService $printDocuments,
    ) {}

    public function index(Request $request): View
    {
        $staff = $this->staff($request);

        $payslips = PayrollItem::query()
            ->with('run')
            ->where('staff_id', $staff->id)
            ->whereHas('run', function ($query) {
                $query->whereIn('status', ['approved', 'posted']);
            })
            ->orderByDesc('pay_period_year')
            ->orderByDesc('pay_period_month')
            ->orderByDesc('id')
            ->paginate(24);

        return view('employee.finances.index', [
            'staff' => $staff,
            'payslips' => $payslips,
        ]);
    }

    public function show(Request $request, PayrollItem $payrollItem): View
    {
        $staff = $this->staff($request);
        $this->assertOwnPayslip($staff->id, $payrollItem);
        $this->assertReleased($payrollItem);

        $breakdown = $payrollItem->breakdown();
        abort_if($breakdown === [], 404);

        $run = $payrollItem->run;
        $period = $run?->periodLabel() ?? $this->periodLabel($payrollItem);

        return $this->printDocuments->render('hr.payroll.print', [
            'documentTitle' => ($breakdown['payroll_scheme'] ?? 'employee') === 'withholding'
                ? 'Consultant Payment Statement'
                : 'Monthly Payslip',
            'documentSubtitle' => ($breakdown['employee_name'] ?? $staff->fullName()).' · '.$period,
            'documentRef' => $this->printDocuments->documentRef('PAY', $payrollItem->payslip_number),
            'metaRows' => [],
            'breakdown' => $breakdown,
            'payPeriod' => $period,
            'hideActions' => false,
            'backUrl' => route('employee.finances.index'),
            'pdfUrl' => route('employee.finances.payslips.pdf', $payrollItem),
            'bodyClass' => 'tich-payslip-page',
        ]);
    }

    public function pdf(Request $request, PayrollItem $payrollItem): StreamedResponse
    {
        $staff = $this->staff($request);
        $this->assertOwnPayslip($staff->id, $payrollItem);
        $this->assertReleased($payrollItem);

        $breakdown = $payrollItem->breakdown();
        abort_if($breakdown === [], 404);

        $run = $payrollItem->run;
        $period = $run?->periodLabel() ?? $this->periodLabel($payrollItem);
        $slug = Str::slug(($breakdown['employee_name'] ?? $staff->fullName()).'-'.$period);

        return $this->printDocuments->downloadPdf(
            'hr.payroll.print',
            [
                'documentTitle' => ($breakdown['payroll_scheme'] ?? 'employee') === 'withholding'
                    ? 'Consultant Payment Statement'
                    : 'Monthly Payslip',
                'documentSubtitle' => ($breakdown['employee_name'] ?? $staff->fullName()).' · '.$period,
                'documentRef' => $this->printDocuments->documentRef('PAY', $payrollItem->payslip_number),
                'metaRows' => [],
                'breakdown' => $breakdown,
                'payPeriod' => $period,
                'hideActions' => true,
                'bodyClass' => 'tich-payslip-page',
            ],
            'payslip-'.$slug.'.pdf',
        );
    }

    private function staff(Request $request): \App\Models\Staff
    {
        $staff = $request->attributes->get('portal_staff')
            ?? $this->employeePortal->staffForUser($request->user());

        abort_unless($staff, 403);

        return $staff;
    }

    private function assertOwnPayslip(int $staffId, PayrollItem $payrollItem): void
    {
        abort_unless((int) $payrollItem->staff_id === $staffId, 403);
    }

    private function assertReleased(PayrollItem $payrollItem): void
    {
        $payrollItem->loadMissing('run');
        abort_unless(
            $payrollItem->run && in_array($payrollItem->run->status, ['approved', 'posted'], true),
            404
        );
    }

    private function periodLabel(PayrollItem $payrollItem): string
    {
        $month = (int) $payrollItem->pay_period_month;
        $year = (int) $payrollItem->pay_period_year;

        if ($month < 1 || $month > 12 || $year < 1) {
            return 'Pay period';
        }

        return \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y');
    }
}

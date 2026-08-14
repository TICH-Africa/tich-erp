<?php

namespace App\Services;

use App\Models\Finance\Invoice;
use App\Models\Finance\Payment;
use App\Models\Finance\StudentAccount;
use App\Models\PayrollRun;
use App\Models\Staff;
use App\Models\AccountLedger;
use Illuminate\Support\Facades\DB;

class FinanceDashboardStatsService
{
    public function studentFinanceChartData(): array
    {
        return [
            'invoicesByStatus' => $this->invoicesByStatus(),
            'paymentsByMethod' => $this->paymentsByMethod(),
            'accountsByClearance' => $this->accountsByClearance(),
        ];
    }

    public function employeeFinanceChartData(): array
    {
        return [
            'payrollRunsByStatus' => $this->payrollRunsByStatus(),
            'staffByPayrollScheme' => $this->staffByPayrollScheme(),
        ];
    }

    public function financeRecordsChartData(): array
    {
        return [
            'ledgerByTransactionType' => $this->ledgerByTransactionType(),
            'invoicesByType' => $this->invoicesByType(),
        ];
    }

    private function invoicesByStatus(): array
    {
        $rows = Invoice::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->map(fn ($row) => $this->label($row->status))->all(),
            'values' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    private function paymentsByMethod(): array
    {
        $rows = Payment::query()
            ->select('payment_method', DB::raw('COUNT(*) as total'))
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->map(fn ($row) => $this->label($row->payment_method))->all(),
            'values' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    private function accountsByClearance(): array
    {
        $cleared = StudentAccount::query()->where('is_cleared', true)->count();
        $credit = StudentAccount::query()->where('is_cleared', false)->where('outstanding_balance', '<=', 0)->where('credit_balance', '>', 0)->count();
        $notCleared = StudentAccount::count() - $cleared - $credit;

        return [
            'labels' => ['Cleared', 'Not cleared', 'Credit balance'],
            'values' => [(int) $cleared, (int) max(0, $notCleared), (int) $credit],
        ];
    }

    private function payrollRunsByStatus(): array
    {
        $rows = PayrollRun::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->map(fn ($row) => $this->label($row->status))->all(),
            'values' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    private function staffByPayrollScheme(): array
    {
        $employee = Staff::query()
            ->where('employment_status', 'active')
            ->whereNotNull('payroll_scheme')
            ->where('payroll_scheme', '!=', 'withholding')
            ->count();

        $withholding = Staff::query()
            ->where('employment_status', 'active')
            ->where('payroll_scheme', 'withholding')
            ->count();

        $unset = Staff::query()
            ->where('employment_status', 'active')
            ->whereNull('payroll_scheme')
            ->count();

        return [
            'labels' => ['Employee scheme', 'Withholding', 'Not set'],
            'values' => [(int) $employee, (int) $withholding, (int) $unset],
        ];
    }

    private function ledgerByTransactionType(): array
    {
        $rows = AccountLedger::query()
            ->select('transaction_type', DB::raw('COUNT(*) as total'))
            ->groupBy('transaction_type')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return [
            'labels' => $rows->map(fn ($row) => $this->label($row->transaction_type))->all(),
            'values' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    private function invoicesByType(): array
    {
        $rows = Invoice::query()
            ->select('invoice_type', DB::raw('COUNT(*) as total'))
            ->groupBy('invoice_type')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->map(fn ($row) => $this->label($row->invoice_type))->all(),
            'values' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    private function label(?string $value): string
    {
        if ($value === null || $value === '') {
            return 'Unspecified';
        }

        return ucfirst(str_replace('_', ' ', $value));
    }
}

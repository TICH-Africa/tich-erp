<?php

namespace App\Services\Finance;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\StudentAccount;
use Illuminate\Support\Collection;

class FinanceDashboardStatsService
{
    public function __construct(
        protected LedgerService $ledger,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        $accountsReceivable = (float) StudentAccount::query()->sum('outstanding_balance');
        $collectedToday = (float) Payment::query()->whereDate('payment_date', today())->sum('amount');
        $openInvoices = Invoice::query()->whereIn('status', ['issued', 'partial', 'overdue'])->count();
        $overdueInvoices = Invoice::query()->where('status', 'overdue')->count();
        $treasuryBalance = $this->ledger->accountBalances()[config('finance.main_treasury_account')] ?? 0.0;

        return [
            'accounts_receivable' => $accountsReceivable,
            'collected_today' => $collectedToday,
            'open_invoices' => $openInvoices,
            'overdue_invoices' => $overdueInvoices,
            'treasury_balance' => $treasuryBalance,
            'recent_payments' => Payment::query()
                ->with(['student.applicant', 'invoice'])
                ->orderByDesc('payment_date')
                ->limit(8)
                ->get(),
            'recent_invoices' => Invoice::query()
                ->with(['student.applicant', 'student.program'])
                ->orderByDesc('issue_date')
                ->limit(8)
                ->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function reportSuite(): array
    {
        $balances = $this->ledger->accountBalances();
        $trialBalance = $this->ledger->trialBalance();

        $assets = $this->sumByType($balances, 'asset');
        $liabilities = $this->sumByType($balances, 'liability');
        $equity = $this->sumByType($balances, 'equity');
        $revenue = $this->sumByType($balances, 'revenue');
        $expenses = $this->sumByType($balances, 'expense');

        return [
            'trial_balance' => $trialBalance,
            'balance_sheet' => [
                'assets' => $assets,
                'liabilities' => $liabilities,
                'equity' => $equity,
                'total_liabilities_equity' => round($liabilities + $equity, 2),
            ],
            'income_statement' => [
                'revenue' => $revenue,
                'expenses' => $expenses,
                'net_income' => round($revenue - $expenses, 2),
            ],
            'cashflow' => [
                'operating' => (float) Payment::query()->sum('amount'),
                'investing' => 0.0,
                'financing' => 0.0,
            ],
            'general_ledger' => $this->ledger->recentEntries(100),
        ];
    }

    /**
     * @param  array<string, float>  $balances
     */
    private function sumByType(array $balances, string $type): float
    {
        $accounts = \App\Models\ChartOfAccount::query()
            ->where('account_type', $type)
            ->pluck('account_code');

        return round(collect($balances)->only($accounts->all())->sum(), 2);
    }
}

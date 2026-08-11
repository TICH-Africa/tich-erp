<?php

namespace App\Services\Finance;

use App\Models\AccountLedger;
use App\Models\ChartOfAccount;

class FinanceReportService
{
    public function __construct(
        protected LedgerService $ledger,
    ) {}

    public function title(string $report): string
    {
        return match ($report) {
            'trial_balance' => 'Trial Balance',
            'balance_sheet' => 'Balance Sheet',
            'income_statement' => 'Statement of Comprehensive Income',
            'cashflow' => 'Statement of Cash Flows',
            'general_ledger' => 'General Ledger',
            default => 'Financial Report',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function build(string $report): array
    {
        return match ($report) {
            'trial_balance' => $this->trialBalance(),
            'balance_sheet' => $this->balanceSheet(),
            'income_statement' => $this->incomeStatement(),
            'cashflow' => $this->cashflow(),
            'general_ledger' => $this->generalLedger(),
            default => $this->trialBalance(),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function trialBalance(): array
    {
        $trial = $this->ledger->trialBalance();

        return [
            'report' => 'trial_balance',
            'title' => $this->title('trial_balance'),
            'as_at' => now()->toDateString(),
            'rows' => $trial['rows'],
            'total_debit' => $trial['total_debit'],
            'total_credit' => $trial['total_credit'],
            'is_balanced' => abs($trial['total_debit'] - $trial['total_credit']) < 0.01,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function balanceSheet(): array
    {
        $sections = [
            $this->accountSection('Assets', 'asset'),
            $this->accountSection('Liabilities', 'liability'),
            $this->accountSection('Equity', 'equity'),
        ];

        $totalAssets = $sections[0]['total'];
        $totalLiabilitiesEquity = round($sections[1]['total'] + $sections[2]['total'], 2);

        return [
            'report' => 'balance_sheet',
            'title' => $this->title('balance_sheet'),
            'as_at' => now()->toDateString(),
            'sections' => $sections,
            'total_assets' => $totalAssets,
            'total_liabilities_equity' => $totalLiabilitiesEquity,
            'is_balanced' => abs($totalAssets - $totalLiabilitiesEquity) < 0.01,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function incomeStatement(): array
    {
        $revenue = $this->accountSection('Revenue', 'revenue');
        $expenses = $this->accountSection('Expenses', 'expense');
        $netIncome = round($revenue['total'] - $expenses['total'], 2);

        return [
            'report' => 'income_statement',
            'title' => $this->title('income_statement'),
            'period_label' => 'For the period ended '.now()->format('d M Y'),
            'revenue' => $revenue,
            'expenses' => $expenses,
            'net_income' => $netIncome,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function cashflow(): array
    {
        $cashCodes = ChartOfAccount::query()
            ->where('is_active', 1)
            ->where(function ($query) {
                $query->where('account_category', 'Cash')
                    ->orWhere('account_code', config('finance.main_treasury_account'));
            })
            ->pluck('account_code')
            ->all();

        $studentReceipts = (float) AccountLedger::query()
            ->where('transaction_type', 'student_payment')
            ->whereIn('debit_account_code', $cashCodes)
            ->sum('debit_amount');

        $expenseCodes = ChartOfAccount::query()
            ->where('account_type', 'expense')
            ->pluck('account_code')
            ->all();

        $expensePayments = (float) AccountLedger::query()
            ->whereIn('credit_account_code', $cashCodes)
            ->whereIn('debit_account_code', $expenseCodes)
            ->sum('credit_amount');

        $investingOutflows = (float) AccountLedger::query()
            ->whereIn('credit_account_code', $cashCodes)
            ->where('transaction_type', 'like', '%asset%')
            ->sum('credit_amount');

        $financingInflows = (float) AccountLedger::query()
            ->whereIn('debit_account_code', $cashCodes)
            ->where('transaction_type', 'like', '%equity%')
            ->sum('debit_amount');

        $operating = round($studentReceipts - $expensePayments, 2);
        $investing = round(0 - $investingOutflows, 2);
        $financing = round($financingInflows, 2);
        $netChange = round($operating + $investing + $financing, 2);

        $closingCash = round(collect($this->ledger->accountBalances())->only($cashCodes)->sum(), 2);

        return [
            'report' => 'cashflow',
            'title' => $this->title('cashflow'),
            'period_label' => 'For the period ended '.now()->format('d M Y'),
            'sections' => [
                [
                    'title' => 'Operating activities',
                    'rows' => [
                        ['label' => 'Cash received from student fees and collections', 'amount' => $studentReceipts],
                        ['label' => 'Cash paid for operating expenses', 'amount' => round(0 - $expensePayments, 2)],
                    ],
                    'total' => $operating,
                ],
                [
                    'title' => 'Investing activities',
                    'rows' => [
                        ['label' => 'Purchase of assets and capital expenditure', 'amount' => round(0 - $investingOutflows, 2)],
                    ],
                    'total' => $investing,
                ],
                [
                    'title' => 'Financing activities',
                    'rows' => [
                        ['label' => 'Capital and financing receipts', 'amount' => $financingInflows],
                    ],
                    'total' => $financing,
                ],
            ],
            'net_change_in_cash' => $netChange,
            'closing_cash_balance' => $closingCash,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function generalLedger(): array
    {
        $accounts = ChartOfAccount::query()->pluck('account_name', 'account_code');
        $entries = AccountLedger::query()
            ->orderBy('ledger_date')
            ->orderBy('id')
            ->limit(500)
            ->get()
            ->map(function (AccountLedger $entry) use ($accounts) {
                return [
                    'id' => $entry->id,
                    'ledger_date' => $entry->ledger_date?->format('Y-m-d'),
                    'ledger_date_display' => $entry->ledger_date?->format('d M Y'),
                    'transaction_type' => str_replace('_', ' ', $entry->transaction_type),
                    'debit_account_code' => $entry->debit_account_code,
                    'debit_account_name' => $accounts[$entry->debit_account_code] ?? $entry->debit_account_code,
                    'credit_account_code' => $entry->credit_account_code,
                    'credit_account_name' => $accounts[$entry->credit_account_code] ?? $entry->credit_account_code,
                    'amount' => round(max((float) $entry->debit_amount, (float) $entry->credit_amount), 2),
                    'narration' => $entry->narration,
                    'source_module' => $entry->source_module,
                    'reference_id' => $entry->reference_id,
                ];
            });

        return [
            'report' => 'general_ledger',
            'title' => $this->title('general_ledger'),
            'period_label' => 'All posted journal entries',
            'rows' => $entries,
            'entry_count' => $entries->count(),
        ];
    }

    /**
     * @return array{title: string, rows: list<array{account_code: string, account_name: string, amount: float}>, total: float}
     */
    private function accountSection(string $title, string $type): array
    {
        $balances = $this->ledger->accountBalances();
        $rows = ChartOfAccount::query()
            ->where('account_type', $type)
            ->where('is_active', 1)
            ->orderBy('account_code')
            ->get()
            ->map(function (ChartOfAccount $account) use ($balances) {
                $amount = round($balances[$account->account_code] ?? 0.0, 2);

                return [
                    'account_code' => $account->account_code,
                    'account_name' => $account->account_name,
                    'amount' => $amount,
                ];
            })
            ->filter(fn (array $row) => abs($row['amount']) >= 0.01)
            ->values()
            ->all();

        return [
            'title' => $title,
            'rows' => $rows,
            'total' => round(collect($rows)->sum('amount'), 2),
        ];
    }
}

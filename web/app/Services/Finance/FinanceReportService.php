<?php

namespace App\Services\Finance;

use App\Models\AccountLedger;
use App\Models\ChartOfAccount;

class FinanceReportService
{
    public function __construct(
        protected LedgerService $ledger,
        protected AccountsReceivableService $ar,
        protected FinanceAuditService $auditTrail,
    ) {}

    public function title(string $report): string
    {
        return match ($report) {
            'trial_balance' => 'Trial Balance',
            'balance_sheet' => 'Balance Sheet',
            'income_statement' => 'Statement of Comprehensive Income',
            'cashflow' => 'Statement of Cash Flows',
            'general_ledger' => 'General Ledger',
            'ar_aging' => 'Accounts Receivable Ageing',
            'ap_aging' => 'Accounts Payable Ageing',
            'payroll_summary' => 'Institutional Payroll Summary',
            'finance_audit' => 'Finance Audit Trail',
            default => 'Financial Report',
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function build(string $report, array $filters = []): array
    {
        return match ($report) {
            'trial_balance' => $this->trialBalance(),
            'balance_sheet' => $this->balanceSheet(),
            'income_statement' => $this->incomeStatement(),
            'cashflow' => $this->cashflow(),
            'general_ledger' => $this->generalLedger(),
            'ar_aging' => $this->arAging(),
            'ap_aging' => $this->apAging(),
            'payroll_summary' => $this->payrollSummary(),
            'finance_audit' => $this->financeAuditReport($filters),
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

    /**
     * @return array<string, mixed>
     */
    public function arAging(): array
    {
        $aging = $this->ar->agingReport();
        $detailRows = [];

        foreach ($aging['buckets'] as $bucketKey => $bucket) {
            foreach ($bucket['invoices'] as $row) {
                $invoice = $row['invoice'];
                $detailRows[] = [
                    'bucket' => $bucketKey,
                    'bucket_label' => $this->ar->bucketLabel($bucketKey),
                    'invoice_number' => $invoice->invoice_number,
                    'student_name' => $invoice->student?->displayName(),
                    'registration_number' => $invoice->student?->registration_number,
                    'due_date' => $invoice->due_date?->format('Y-m-d'),
                    'days_past_due' => $row['days_past_due'],
                    'balance' => (float) $invoice->balance,
                    'status' => $invoice->status,
                ];
            }
        }

        return [
            'report' => 'ar_aging',
            'title' => $this->title('ar_aging'),
            'as_at' => $aging['as_at'],
            'total_outstanding' => $aging['total_outstanding'],
            'invoice_count' => $aging['invoice_count'],
            'buckets' => collect($aging['buckets'])->map(fn (array $bucket, string $key) => [
                'key' => $key,
                'label' => $bucket['label'],
                'count' => $bucket['count'],
                'total' => $bucket['total'],
            ])->values()->all(),
            'rows' => $detailRows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function apAging(): array
    {
        return [
            'report' => 'ap_aging',
            'title' => $this->title('ap_aging'),
            'as_at' => now()->toDateString(),
            'total_outstanding' => 0.0,
            'vendor_count' => 0,
            'buckets' => collect(AccountsReceivableService::BUCKET_KEYS)->map(fn (string $key) => [
                'key' => $key,
                'label' => $this->ar->bucketLabel($key),
                'count' => 0,
                'total' => 0.0,
            ])->values()->all(),
            'rows' => [],
            'empty_message' => 'Accounts payable ageing is not available yet — no vendor invoices or AP module data.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payrollSummary(): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('payroll_runs')) {
            return [
                'report' => 'payroll_summary',
                'title' => $this->title('payroll_summary'),
                'period_label' => 'Approved and posted payroll runs',
                'as_at' => now()->toDateString(),
                'rows' => [],
                'totals' => ['runs' => 0, 'staff' => 0, 'gross' => 0.0, 'net' => 0.0, 'paye' => 0.0],
            ];
        }

        $runs = \App\Models\PayrollRun::query()
            ->whereIn('status', [\App\Models\PayrollRun::STATUS_APPROVED, \App\Models\PayrollRun::STATUS_POSTED])
            ->orderByDesc('pay_period_year')
            ->orderByDesc('pay_period_month')
            ->limit(24)
            ->get();

        $rows = $runs->map(fn (\App\Models\PayrollRun $run) => [
            'run_number' => $run->run_number,
            'period' => $run->periodLabel(),
            'status' => $run->status,
            'staff_count' => $run->staff_count,
            'total_gross' => (float) $run->total_gross,
            'total_net' => (float) $run->total_net,
            'total_paye' => (float) $run->total_paye,
            'total_nssf' => (float) $run->total_nssf,
            'total_sha' => (float) $run->total_sha,
            'total_ahl' => (float) $run->total_ahl,
            'posted_at' => $run->posted_at?->format('Y-m-d'),
        ])->all();

        return [
            'report' => 'payroll_summary',
            'title' => $this->title('payroll_summary'),
            'period_label' => 'Approved and posted payroll runs',
            'as_at' => now()->toDateString(),
            'rows' => $rows,
            'totals' => [
                'runs' => count($rows),
                'staff' => (int) $runs->sum('staff_count'),
                'gross' => round((float) $runs->sum('total_gross'), 2),
                'net' => round((float) $runs->sum('total_net'), 2),
                'paye' => round((float) $runs->sum('total_paye'), 2),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function financeAuditReport(array $filters = []): array
    {
        $logs = $this->auditTrail->query($filters)->limit(500)->get();

        $rows = $logs->map(fn ($log) => [
            'id' => $log->id,
            'created_at' => $log->created_at?->format('Y-m-d H:i'),
            'action' => $log->action,
            'entity_type' => $log->entity_type,
            'entity_id' => $log->entity_id,
            'status' => $log->status ?? 'success',
            'user_email' => $log->user?->email,
            'reason' => $log->reason,
        ])->all();

        return [
            'report' => 'finance_audit',
            'title' => $this->title('finance_audit'),
            'period_label' => 'Finance module actions (most recent 500)',
            'as_at' => now()->toDateString(),
            'rows' => $rows,
            'entry_count' => count($rows),
            'filters' => $filters,
        ];
    }
}

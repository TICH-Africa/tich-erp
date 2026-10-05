<?php

namespace App\Services\Finance;

use App\Models\AccountLedger;
use App\Models\ChartOfAccount;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinanceReportService
{
    public function __construct(
        protected LedgerService $ledger,
        protected AccountsReceivableService $ar,
        protected FinanceAuditService $auditTrail,
        protected ChartOfAccountService $accounts,
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
            'reconciliation' => 'Reconciliation Report',
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
            'trial_balance' => $this->trialBalance($filters),
            'balance_sheet' => $this->balanceSheet($filters),
            'income_statement' => $this->incomeStatement($filters),
            'cashflow' => $this->cashflow($filters),
            'general_ledger' => $this->generalLedger($filters),
            'ar_aging' => $this->arAging(),
            'ap_aging' => $this->apAging(),
            'payroll_summary' => $this->payrollSummary(),
            'finance_audit' => $this->financeAuditReport($filters),
            'reconciliation' => $this->reconciliation($filters),
            default => $this->trialBalance($filters),
        };
    }

    /**
     * Resolve the profit and loss window. Presets are month to date, financial year,
     * custom dates and all time.
     *
     * @param  array<string, mixed>  $filters
     * @return array{preset: string, from: string, to: string, label: string}
     */
    public function resolveIncomeStatementPeriod(array $filters = []): array
    {
        $preset = (string) ($filters['period'] ?? 'all');
        $today = now()->startOfDay();

        return match ($preset) {
            'fy' => $this->financialYearWindow($today),
            'custom' => [
                'preset' => 'custom',
                'from' => $this->validDate($filters['from'] ?? null) ?? $today->copy()->startOfMonth()->toDateString(),
                'to' => $this->validDate($filters['to'] ?? null) ?? $today->toDateString(),
                'label' => 'Custom period',
            ],
            'all' => [
                'preset' => 'all',
                'from' => '',
                'to' => '',
                'label' => 'All posted activity',
            ],
            default => [
                'preset' => 'mtd',
                'from' => $today->copy()->startOfMonth()->toDateString(),
                'to' => $today->toDateString(),
                'label' => 'Month to date',
            ],
        };
    }

    /**
     * @return array{preset: string, from: string, to: string, label: string}
     */
    private function financialYearWindow(\Carbon\CarbonInterface $today): array
    {
        // Kenya institutional FY: 1 July to 30 June (configurable).
        $startMonth = (int) config('finance.financial_year_start_month', 7);
        $year = (int) $today->year;
        $fyStart = $today->copy()->month($startMonth)->day(1)->startOfDay();

        if ($today->lt($fyStart)) {
            $fyStart->subYear();
        }

        $fyEnd = $fyStart->copy()->addYear()->subDay();

        return [
            'preset' => 'fy',
            'from' => $fyStart->toDateString(),
            'to' => min($today->toDateString(), $fyEnd->toDateString()),
            'label' => 'Financial year (from '.$fyStart->format('d M Y').')',
        ];
    }

    private function validDate(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function trialBalance(array $filters = []): array
    {
        $trial = $this->ledger->trialBalance();
        $revenue = $this->netOf($this->accountSection('Revenue', 'revenue'));
        $expenses = $this->netOf($this->accountSection('Expenses', 'expense'));

        return [
            'report' => 'trial_balance',
            'title' => $this->title('trial_balance'),
            'as_at' => now()->toDateString(),
            'period_label' => 'As at '.now()->format('d M Y'),
            'rows' => $trial['rows'],
            'total_debit' => $trial['total_debit'],
            'total_credit' => $trial['total_credit'],
            'is_balanced' => abs($trial['total_debit'] - $trial['total_credit']) < 0.01,
            'revenue' => $revenue,
            'expenses' => $expenses,
            'net_income' => round($revenue - $expenses, 2),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function balanceSheet(array $filters = []): array
    {
        $assets = $this->accountSection('Assets', 'asset', $filters);
        $liabilities = $this->accountSection('Liabilities', 'liability', $filters);
        $equity = $this->accountSection('Equity', 'equity', $filters);

        // Revenue and expense balances are closed into equity so the statement
        // balances: profit increases equity, a loss reduces it.
        $netIncome = $this->periodNetIncome($filters);

        if (abs($netIncome) >= 0.01) {
            $hasPeriod = ! empty($filters['from']) || ! empty($filters['to']);
            $label = match (true) {
                ! $hasPeriod => 'Retained surplus / (deficit)',
                $netIncome >= 0 => 'Profit for the period',
                default => 'Loss for the period',
            };

            $equity['rows'][] = [
                'account_code' => 'PROFIT',
                'account_name' => $label,
                'amount' => $netIncome,
                'level' => 0,
                'own_amount' => $netIncome,
                'is_group' => false,
                'has_children' => false,
            ];
            $equity['total'] = round($equity['total'] + $netIncome, 2);
        }

        $totalAssets = $this->netOf($assets);
        $totalLiabilitiesEquity = round($this->netOf($liabilities) + $this->netOf($equity), 2);

        return [
            'report' => 'balance_sheet',
            'title' => $this->title('balance_sheet'),
            'as_at' => now()->toDateString(),
            'period_label' => 'As at '.now()->format('d M Y'),
            'sections' => [$assets, $liabilities, $equity],
            'total_assets' => $totalAssets,
            'total_liabilities_equity' => $totalLiabilitiesEquity,
            'is_balanced' => abs($totalAssets - $totalLiabilitiesEquity) < 0.01,
            'difference' => round($totalAssets - $totalLiabilitiesEquity, 2),
        ];
    }

    /**
     * Profit and loss. Every revenue and expense account is listed with its own amount
     * and its children's movement, so nothing posted to a child account is missed, and
     * the statement is tied back to the general ledger for the same window.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function incomeStatement(array $filters = []): array
    {
        $period = $this->resolveIncomeStatementPeriod($filters);
        $viewMode = (($filters['view'] ?? 'standard') === 'full') ? 'full' : 'standard';
        $window = ['from' => $period['from'] !== '' ? $period['from'] : null, 'to' => $period['to'] !== '' ? $period['to'] : null];
        $from = $window['from'];
        $to = $window['to'];

        $revenue = $this->accountSection('Revenue', 'revenue', $window);
        $expenses = $this->accountSection('Expenses', 'expense', $window);

        $totalRevenue = $this->netOf($revenue);
        $totalExpenses = $this->netOf($expenses);
        $netIncome = round($totalRevenue - $totalExpenses, 2);

        $check = $this->ledgerCrossCheck($from, $to, $totalRevenue, $totalExpenses);

        $payload = [
            'report' => 'income_statement',
            'title' => $this->title('income_statement'),
            'period' => $period,
            'period_label' => $period['preset'] === 'all'
                ? 'All posted activity'
                : $this->periodLabel($from, $to),
            'from' => $from,
            'to' => $to,
            'view_mode' => $viewMode,
            'revenue' => $revenue,
            'expenses' => $expenses,
            'sections' => $viewMode === 'full'
                ? [
                    $this->accountSection('Assets', 'asset', $window),
                    $this->accountSection('Liabilities', 'liability', $window),
                    $this->accountSection('Equity', 'equity', $window),
                ]
                : [],
            'total_revenue' => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'net_income' => $netIncome,
            'is_profit' => $netIncome >= 0,
            'account_count' => (int) $revenue['account_count'] + (int) $expenses['account_count'],
            'ties_to_ledger' => $check['revenue_difference'] < 0.01 && $check['expense_difference'] < 0.01,
            'revenue_difference' => $check['revenue_difference'],
            'expense_difference' => $check['expense_difference'],
        ];

        return $payload;
    }

    /**
     * Recount revenue and expenditure straight from the journal entries in the period
     * and compare them with the figures the statement is built from. The statement is
     * assembled from account balances, so this is an independent tie-out.
     *
     * @return array{revenue: float, expenses: float, revenue_difference: float, expense_difference: float}
     */
    private function ledgerCrossCheck(?string $from, ?string $to, float $statementRevenue, float $statementExpenses): array
    {
        $types = ChartOfAccount::query()->pluck('account_type', 'account_code')->all();

        $entries = AccountLedger::query()
            ->where('is_reversed', 0)
            ->when($from, fn ($q) => $q->where('ledger_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('ledger_date', '<=', $to))
            ->get(['debit_account_code', 'credit_account_code', 'debit_amount', 'credit_amount']);

        $revenue = 0.0;
        $expenses = 0.0;

        foreach ($entries as $entry) {
            $debitCode = (string) $entry->debit_account_code;
            $creditCode = (string) $entry->credit_account_code;

            if (($types[$creditCode] ?? null) === 'revenue') {
                $revenue += (float) $entry->credit_amount;
            }

            if (($types[$debitCode] ?? null) === 'revenue') {
                $revenue -= (float) $entry->debit_amount;
            }

            if (($types[$debitCode] ?? null) === 'expense') {
                $expenses += (float) $entry->debit_amount;
            }

            if (($types[$creditCode] ?? null) === 'expense') {
                $expenses -= (float) $entry->credit_amount;
            }
        }

        return [
            'revenue' => round($revenue, 2),
            'expenses' => round($expenses, 2),
            'revenue_difference' => round(abs($statementRevenue - $revenue), 2),
            'expense_difference' => round(abs($statementExpenses - $expenses), 2),
        ];
    }

    /**
     * Cash flow derived from the ledger rather than from transaction type names, so
     * manual journals and any new posting module are captured as well.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function cashflow(array $filters = []): array
    {
        $from = $this->periodStart($filters);
        $to = $this->periodEnd($filters);

        $cashCodes = $this->cashAccountCodes();
        $types = ChartOfAccount::query()->pluck('account_type', 'account_code');

        $entries = AccountLedger::query()
            ->where('is_reversed', 0)
            ->when($from, fn ($q) => $q->where('ledger_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('ledger_date', '<=', $to))
            ->get(['debit_account_code', 'credit_account_code', 'debit_amount', 'credit_amount']);

        $operatingIn = 0.0;
        $operatingOut = 0.0;
        $investingOut = 0.0;
        $investingIn = 0.0;
        $financingIn = 0.0;
        $financingOut = 0.0;

        foreach ($entries as $entry) {
            $debitCode = (string) $entry->debit_account_code;
            $creditCode = (string) $entry->credit_account_code;
            $debitInCash = in_array($debitCode, $cashCodes, true);
            $creditInCash = in_array($creditCode, $cashCodes, true);

            if ($debitInCash === $creditInCash) {
                // Cash to cash or neither side is cash: not a cash movement.
                continue;
            }

            $counterpart = $debitInCash ? $creditCode : $debitCode;
            $type = $types[$counterpart] ?? null;
            $amount = round((float) ($debitInCash ? $entry->credit_amount : $entry->debit_amount), 2);

            if ($debitInCash) {
                match ($type) {
                    'revenue' => $operatingIn += $amount,
                    'asset' => $investingIn += $amount,
                    'liability', 'equity' => $financingIn += $amount,
                    default => $operatingIn += $amount,
                };

                continue;
            }

            match ($type) {
                'expense' => $operatingOut += $amount,
                'asset' => $investingOut += $amount,
                'liability', 'equity' => $financingOut += $amount,
                default => $operatingOut += $amount,
            };
        }

        $operating = round($operatingIn - $operatingOut, 2);
        $investing = round($investingIn - $investingOut, 2);
        $financing = round($financingIn - $financingOut, 2);
        $netChange = round($operating + $investing + $financing, 2);

        $openingCash = $from ? $this->cashBalanceUpTo($this->dayBefore($from)) : 0.0;
        $closingCash = $this->cashBalanceUpTo($to);

        return [
            'report' => 'cashflow',
            'title' => $this->title('cashflow'),
            'period_label' => $this->periodLabel($from, $to),
            'from' => $from,
            'to' => $to,
            'sections' => [
                [
                    'title' => 'Operating activities',
                    'rows' => [
                        ['label' => 'Cash received from fees, grants and other income', 'amount' => round($operatingIn, 2)],
                        ['label' => 'Cash paid to suppliers, staff and other operating expenses', 'amount' => round(0 - $operatingOut, 2)],
                    ],
                    'total' => $operating,
                ],
                [
                    'title' => 'Investing activities',
                    'rows' => [
                        ['label' => 'Purchase of assets and capital expenditure', 'amount' => round(0 - $investingOut, 2)],
                        ['label' => 'Proceeds from disposal of assets', 'amount' => round($investingIn, 2)],
                    ],
                    'total' => $investing,
                ],
                [
                    'title' => 'Financing activities',
                    'rows' => [
                        ['label' => 'Capital, loans and other financing receipts', 'amount' => round($financingIn, 2)],
                        ['label' => 'Loan repayments and other financing payments', 'amount' => round(0 - $financingOut, 2)],
                    ],
                    'total' => $financing,
                ],
            ],
            'net_change_in_cash' => $netChange,
            'opening_cash_balance' => $openingCash,
            'closing_cash_balance' => $closingCash,
        ];
    }

    /**
     * Cash, bank and mobile money account codes, including child accounts of a
     * cash account parent.
     *
     * @return list<string>
     */
    private function cashAccountCodes(): array
    {
        $configured = (array) config('finance.cash_accounts', []);

        $codes = ChartOfAccount::query()
            ->where('is_active', 1)
            ->where('account_type', 'asset')
            ->where(function ($query) use ($configured) {
                $query->whereIn('account_code', $configured)
                    ->orWhere('account_name', 'like', '%cash%')
                    ->orWhere('account_name', 'like', '%mpesa%')
                    ->orWhere('account_name', 'like', '%bank%');
            })
            ->pluck('account_code')
            ->map(fn ($code) => (string) $code)
            ->all();

        // A cash account parent holds the money on its children.
        foreach ($codes as $code) {
            $codes = array_values(array_unique(array_merge(
                $codes,
                ChartOfAccount::query()->where('parent_account_code', $code)->pluck('account_code')->map(fn ($c) => (string) $c)->all()
            )));
        }

        return $codes;
    }

    private function cashBalanceUpTo(?string $date): float
    {
        $balances = $this->ledger->signedBalances(null, $date);

        return round(collect($this->cashAccountCodes())
            ->map(fn (string $code) => (float) ($balances[$code] ?? 0.0))
            ->sum(), 2);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function generalLedger(array $filters = []): array
    {
        $from = $this->periodStart($filters);
        $to = $this->periodEnd($filters);
        $accountCode = trim((string) ($filters['account_code'] ?? ''));

        $accounts = ChartOfAccount::query()->pluck('account_name', 'account_code');

        $query = AccountLedger::query()
            ->where('is_reversed', 0)
            ->when($from, fn ($q) => $q->where('ledger_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('ledger_date', '<=', $to))
            ->when($accountCode !== '', fn ($q) => $q->where(function ($inner) use ($accountCode) {
                $inner->where('debit_account_code', $accountCode)->orWhere('credit_account_code', $accountCode);
            }))
            ->orderBy('ledger_date')
            ->orderBy('id');

        $total = (clone $query)->count();

        $entries = $query->limit(2000)->get()->map(function (AccountLedger $entry) use ($accounts) {
            return [
                'id' => $entry->id,
                'ledger_date' => $entry->ledger_date?->format('Y-m-d'),
                'ledger_date_display' => $entry->ledger_date?->format('d M Y'),
                'transaction_type' => str_replace('_', ' ', (string) $entry->transaction_type),
                'debit_account_code' => $entry->debit_account_code,
                'debit_account_name' => $accounts[$entry->debit_account_code] ?? $entry->debit_account_code,
                'credit_account_code' => $entry->credit_account_code,
                'credit_account_name' => $accounts[$entry->credit_account_code] ?? $entry->credit_account_code,
                'debit_amount' => round((float) $entry->debit_amount, 2),
                'credit_amount' => round((float) $entry->credit_amount, 2),
                'amount' => round(max((float) $entry->debit_amount, (float) $entry->credit_amount), 2),
                'narration' => $entry->narration,
                'source_module' => $entry->source_module,
                'reference_id' => $entry->reference_id,
            ];
        });

        return [
            'report' => 'general_ledger',
            'title' => $this->title('general_ledger'),
            'period_label' => $from || $to
                ? $this->periodLabel($from, $to)
                : 'All posted journal entries',
            'from' => $from,
            'to' => $to,
            'account_code' => $accountCode !== '' ? $accountCode : null,
            'rows' => $entries,
            'entry_count' => $total,
            'truncated' => $total > $entries->count(),
        ];
    }

    /**
     * Report lines for one account type, laid out as the account tree.
     *
     * A parent line carries its own movement plus every child, and each child is listed
     * underneath, so no posting is hidden behind a parent balance. Amounts follow the
     * normal balance of the type: revenue is shown as a positive credit, expenses as a
     * positive debit.
     *
     * @param  array<string, mixed>  $filters
     * @return array{title: string, rows: list<array<string, mixed>>, total: float, account_count: int}
     */
    private function accountSection(string $title, ?string $type, array $filters = []): array
    {
        $from = $this->periodStart($filters);
        $to = $this->periodEnd($filters);
        $all = ChartOfAccount::query()->orderBy('account_code')->get()->keyBy('account_code');

        $subjects = $all->filter(
            fn (ChartOfAccount $account) => (int) $account->is_active === 1
                && ($type === null || $account->account_type === $type)
        );

        $direct = $this->ledger->signedBalances($from, $to);
        $rolled = $this->accounts->rollUpBalances($direct);

        $childrenOf = [];

        foreach ($all as $code => $account) {
            $parentCode = $account->parent_account_code;

            if ($parentCode !== null && $parentCode !== '' && $all->has($parentCode)) {
                $childrenOf[$parentCode][] = $code;
            }
        }

        $rows = [];
        $seen = [];
        $listed = 0;

        $append = function (ChartOfAccount $account, int $level) use (&$append, &$rows, &$seen, &$listed, $childrenOf, $all, $subjects, $direct, $rolled): void {
            $code = $account->account_code;

            if (isset($seen[$code])) {
                return;
            }

            $seen[$code] = true;

            $own = round((float) ($direct[$code] ?? 0.0), 2);
            $total = round((float) ($rolled[$code] ?? $own), 2);
            $kids = array_values(array_filter(
                $childrenOf[$code] ?? [],
                fn (string $childCode) => $subjects->has($childCode)
            ));

            if (abs($total) < 0.01 && $kids === []) {
                return;
            }

            $listed++;
            $rows[] = [
                'account_code' => $code,
                'account_name' => $account->account_name,
                'account_type' => $account->account_type,
                'currency' => $account->currency,
                'amount' => $total,
                'own_amount' => $own,
                'level' => $level,
                'is_group' => $kids !== [],
                'has_children' => $kids !== [],
            ];

            foreach ($kids as $childCode) {
                $append($all->get($childCode), $level + 1);
            }
        };

        foreach ($subjects as $code => $account) {
            $parentCode = $account->parent_account_code;

            if ($parentCode === null || $parentCode === '' || ! $subjects->has($parentCode)) {
                $append($account, 0);
            }
        }

        $total = round(collect($rows)->where('level', 0)->sum('amount'), 2);

        return [
            'title' => $title,
            'rows' => $rows,
            'total' => $total,
            'account_count' => $listed,
        ];
    }

    /**
     * Sum of the top level lines of a section, which is what the statement shows.
     *
     * @param  array<string, mixed>  $section
     */
    private function netOf(array $section): float
    {
        return round((float) ($section['total'] ?? 0.0), 2);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function periodNetIncome(array $filters): float
    {
        $revenue = $this->netOf($this->accountSection('Revenue', 'revenue', $filters));
        $expenses = $this->netOf($this->accountSection('Expenses', 'expense', $filters));

        return round($revenue - $expenses, 2);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function periodStart(array $filters): ?string
    {
        $from = trim((string) ($filters['from'] ?? ''));

        return $from !== '' && $this->isDate($from) ? $from : null;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function periodEnd(array $filters): ?string
    {
        $to = trim((string) ($filters['to'] ?? ''));

        return $to !== '' && $this->isDate($to) ? $to : null;
    }

    private function isDate(string $value): bool
    {
        [$year, $month, $day] = array_pad(array_map('intval', explode('-', $value)), 3, 0);

        return $year > 2000 && checkdate($month, $day, $year);
    }

    private function dayBefore(string $date): string
    {
        return now()->parse($date)->subDay()->toDateString();
    }

    private function periodLabel(?string $from, ?string $to): string
    {
        if ($from !== null && $to !== null) {
            return 'For the period '.now()->parse($from)->format('d M Y').' to '.now()->parse($to)->format('d M Y');
        }

        if ($from !== null) {
            return 'For the period from '.now()->parse($from)->format('d M Y');
        }

        if ($to !== null) {
            return 'For the period ended '.now()->parse($to)->format('d M Y');
        }

        return 'For the period ended '.now()->format('d M Y');
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
     * Vendor invoices that are still owed money, aged from their due date with the
     * same buckets the receivable report uses.
     *
     * @return array<string, mixed>
     */
    public function apAging(): array
    {
        if (! Schema::hasTable('accounts_payable') || ! Schema::hasTable('suppliers')) {
            return $this->emptyApAging();
        }

        $suppliers = DB::table('suppliers')->pluck('supplier_name', 'id');

        $invoices = DB::table('accounts_payable')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->where('balance', '>', 0)
            ->orderBy('due_date')
            ->get();

        $buckets = collect(AccountsReceivableService::BUCKET_KEYS)->mapWithKeys(fn (string $key) => [
            $key => ['key' => $key, 'label' => $this->ar->bucketLabel($key), 'count' => 0, 'total' => 0.0, 'invoices' => []],
        ])->all();

        $detailRows = [];
        $totalOutstanding = 0.0;
        $vendorIds = [];

        foreach ($invoices as $invoice) {
            $due = Carbon::parse($invoice->due_date)->startOfDay();
            $days = $due->isFuture() ? 0 : (int) $due->diffInDays(now()->startOfDay());
            $bucket = $this->ar->bucketForDays($days);
            $balance = round((float) $invoice->balance, 2);

            $buckets[$bucket]['count']++;
            $buckets[$bucket]['total'] = round($buckets[$bucket]['total'] + $balance, 2);
            $buckets[$bucket]['invoices'][] = $invoice->invoice_number;

            $totalOutstanding = round($totalOutstanding + $balance, 2);
            $vendorIds[$invoice->supplier_id] = true;

            $detailRows[] = [
                'bucket' => $bucket,
                'bucket_label' => $this->ar->bucketLabel($bucket),
                'invoice_number' => $invoice->invoice_number,
                'supplier_name' => $suppliers[$invoice->supplier_id] ?? ('Supplier #'.$invoice->supplier_id),
                'invoice_date' => Carbon::parse($invoice->invoice_date)->format('Y-m-d'),
                'due_date' => $due->format('Y-m-d'),
                'days_past_due' => $days,
                'total_amount' => round((float) $invoice->total_amount, 2),
                'amount_paid' => round((float) $invoice->amount_paid, 2),
                'balance' => $balance,
                'payment_status' => $invoice->payment_status,
                'three_way_match_status' => $invoice->three_way_match_status,
            ];
        }

        return [
            'report' => 'ap_aging',
            'title' => $this->title('ap_aging'),
            'as_at' => now()->toDateString(),
            'total_outstanding' => $totalOutstanding,
            'vendor_count' => count($vendorIds),
            'invoice_count' => count($detailRows),
            'buckets' => collect($buckets)->map(fn (array $bucket) => [
                'key' => $bucket['key'],
                'label' => $bucket['label'],
                'count' => $bucket['count'],
                'total' => $bucket['total'],
            ])->values()->all(),
            'rows' => $detailRows,
            'empty_message' => $detailRows === []
                ? 'No outstanding vendor invoices: every supplier invoice is fully settled.'
                : '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyApAging(): array
    {
        return [
            'report' => 'ap_aging',
            'title' => $this->title('ap_aging'),
            'as_at' => now()->toDateString(),
            'total_outstanding' => 0.0,
            'vendor_count' => 0,
            'invoice_count' => 0,
            'buckets' => collect(AccountsReceivableService::BUCKET_KEYS)->map(fn (string $key) => [
                'key' => $key,
                'label' => $this->ar->bucketLabel($key),
                'count' => 0,
                'total' => 0.0,
            ])->values()->all(),
            'rows' => [],
            'empty_message' => 'Accounts payable ageing is not available yet - no vendor invoice tables are installed.',
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

    /**
     * Income and expenditure for a period, taken from the account types rather than
     * transaction type names so manual journals, imports and new posting modules all
     * count towards the result.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function reconciliation(array $filters = []): array
    {
        $from = $this->periodStart($filters) ?? now()->subMonth()->toDateString();
        $to = $this->periodEnd($filters) ?? now()->toDateString();

        $accounts = ChartOfAccount::query()->get(['account_code', 'account_name', 'account_type']);
        $names = $accounts->pluck('account_name', 'account_code');
        $types = $accounts->pluck('account_type', 'account_code');

        $entries = AccountLedger::query()
            ->where('is_reversed', 0)
            ->whereBetween('ledger_date', [$from, $to])
            ->orderBy('ledger_date')
            ->orderBy('id')
            ->get();

        $incomeRows = [];
        $expenseRows = [];

        foreach ($entries as $entry) {
            $debitCode = (string) $entry->debit_account_code;
            $creditCode = (string) $entry->credit_account_code;
            $debitAmount = (float) $entry->debit_amount;
            $creditAmount = (float) $entry->credit_amount;

            $base = [
                'date' => $entry->ledger_date?->format('Y-m-d'),
                'date_display' => $entry->ledger_date?->format('d M Y'),
                'type' => str_replace('_', ' ', (string) $entry->transaction_type),
                'narration' => $entry->narration,
                'reference' => $entry->reference_id,
                'source' => $entry->source_module,
                'income' => 0.0,
                'expense' => 0.0,
            ];

            // Income is the net movement on revenue accounts and expenditure the net
            // movement on expense accounts, so credit notes, waivers and reversals
            // reduce the figure they belong to. Anything else is a transfer between
            // balance sheet accounts and neither report counts it.
            $income = round(($types[$creditCode] ?? null) === 'revenue' ? $creditAmount : 0.0, 2)
                - round(($types[$debitCode] ?? null) === 'revenue' ? $debitAmount : 0.0, 2);
            $expense = round(($types[$debitCode] ?? null) === 'expense' ? $debitAmount : 0.0, 2)
                - round(($types[$creditCode] ?? null) === 'expense' ? $creditAmount : 0.0, 2);

            if ($income >= 0.01) {
                $incomeRows[] = array_merge($base, [
                    'category' => $names[$creditCode] ?? $creditCode,
                    'account_code' => $creditCode,
                    'account_name' => $names[$creditCode] ?? $creditCode,
                    'income' => $income,
                ]);
            } elseif ($income <= -0.01) {
                $incomeRows[] = array_merge($base, [
                    'category' => $names[$debitCode] ?? $debitCode,
                    'account_code' => $debitCode,
                    'account_name' => $names[$debitCode] ?? $debitCode,
                    'income' => $income,
                ]);
            }

            if ($expense >= 0.01) {
                $expenseRows[] = array_merge($base, [
                    'category' => $names[$debitCode] ?? $debitCode,
                    'account_code' => $debitCode,
                    'account_name' => $names[$debitCode] ?? $debitCode,
                    'expense' => $expense,
                ]);
            } elseif ($expense <= -0.01) {
                $expenseRows[] = array_merge($base, [
                    'category' => $names[$creditCode] ?? $creditCode,
                    'account_code' => $creditCode,
                    'account_name' => $names[$creditCode] ?? $creditCode,
                    'expense' => $expense,
                ]);
            }
        }

        $incomeTotal = round(collect($incomeRows)->sum('income'), 2);
        $expenseTotal = round(collect($expenseRows)->sum('expense'), 2);

        $incomeByCategory = collect($incomeRows)->groupBy('category')->map(fn ($rows) => [
            'count' => $rows->count(),
            'total' => round($rows->sum('income'), 2),
        ])->sortByDesc('total')->values()->all();

        $expenseByCategory = collect($expenseRows)->groupBy('category')->map(fn ($rows) => [
            'count' => $rows->count(),
            'total' => round($rows->sum('expense'), 2),
        ])->sortByDesc('total')->values()->all();

        $detailRows = collect(array_merge($incomeRows, $expenseRows))
            ->sortBy([['date', 'asc'], ['category', 'asc']])
            ->values()
            ->all();

        $openingBalances = $this->ledger->signedBalances(null, $this->dayBefore((string) $from));
        $cashCodes = $this->cashAccountCodes();
        $openingBalance = round(collect($cashCodes)->sum(fn (string $code) => (float) ($openingBalances[$code] ?? 0.0)), 2);

        return [
            'report' => 'reconciliation',
            'title' => $this->title('reconciliation'),
            'period_label' => 'From '.now()->parse($from)->format('d M Y').' to '.now()->parse($to)->format('d M Y'),
            'filters' => $filters,
            'from' => $from,
            'to' => $to,
            'opening_balance' => $openingBalance,
            'income' => [
                'categories' => $incomeByCategory,
                'total' => $incomeTotal,
                'rows' => $incomeRows,
            ],
            'expenses' => [
                'categories' => $expenseByCategory,
                'total' => $expenseTotal,
                'rows' => $expenseRows,
            ],
            'net_position' => round($incomeTotal - $expenseTotal, 2),
            'closing_balance' => round($openingBalance + $incomeTotal - $expenseTotal, 2),
            'rows' => $detailRows,
            'entry_count' => count($detailRows),
        ];
    }
}

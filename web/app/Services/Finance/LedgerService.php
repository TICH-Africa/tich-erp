<?php

namespace App\Services\Finance;

use App\Models\AccountExpenseEntry;
use App\Models\AccountLedger;
use App\Models\ChartOfAccount;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LedgerService
{
    /**
     * The chart of accounts for the current request, loaded once and reused so bulk
     * invoice and payment runs do not re-query it for every posting.
     *
     * @var Collection<int, ChartOfAccount>|null
     */
    private ?Collection $chartAccounts = null;

    public function postEntry(
        string $transactionType,
        string $debitAccountCode,
        string $creditAccountCode,
        float $amount,
        string $narration,
        string $sourceModule,
        ?string $referenceTable = null,
        ?string $referenceId = null,
        ?int $recordedByStaffId = null,
        ?string $ledgerDate = null,
    ): AccountLedger {
        return AccountLedger::query()->create([
            'ledger_date' => $ledgerDate ?? now()->toDateString(),
            'transaction_type' => $transactionType,
            'debit_account_code' => $debitAccountCode,
            'credit_account_code' => $creditAccountCode,
            'debit_amount' => round($amount, 2),
            'credit_amount' => round($amount, 2),
            'narration' => $narration,
            'reference_table' => $referenceTable,
            'reference_id' => $referenceId,
            'source_module' => $sourceModule,
            'recorded_by' => $recordedByStaffId ?? $this->systemStaffId(),
        ]);
    }

    public function postInvoiceRaised(float $amount, string $invoiceNumber, ?int $recordedByStaffId = null, ?string $invoiceType = null): AccountLedger
    {
        return $this->postEntry(
            'invoice_raised',
            $this->resolveChartCode((string) config('finance.accounts.accounts_receivable'), 'asset', ['receivable', 'debtor'], 'FINANCE_ACCOUNTS_RECEIVABLE_ACCOUNT', 'Student Fees Receivable'),
            $this->revenueAccountForInvoiceType($invoiceType),
            $amount,
            "Invoice raised: {$invoiceNumber}",
            'student_fees',
            'invoices',
            $invoiceNumber,
            $recordedByStaffId,
        );
    }

    public function postStudentPayment(
        float $amount,
        string $paymentNumber,
        string $paymentMethod,
        ?int $recordedByStaffId = null,
        ?string $paymentDate = null,
        ?string $paymentAccountCode = null,
    ): AccountLedger
    {
        $receivingAccountCode = $paymentAccountCode === null || trim($paymentAccountCode) === ''
            ? $this->cashAccountFor($paymentMethod)
            : $this->resolveChartCode(
                $paymentAccountCode,
                'asset',
                [],
                'payment account',
                'Student Payment Receipts',
                true,
            );

        return $this->postEntry(
            'student_payment',
            $receivingAccountCode,
            $this->resolveChartCode((string) config('finance.accounts.accounts_receivable'), 'asset', ['receivable', 'debtor'], 'FINANCE_ACCOUNTS_RECEIVABLE_ACCOUNT', 'Student Fees Receivable'),
            $amount,
            "Student payment: {$paymentNumber}",
            'student_fees',
            'payments',
            $paymentNumber,
            $recordedByStaffId,
            $paymentDate,
        );
    }

    /**
     * Post a distribution recorded against a chart account. Debit-normal accounts
     * (expense, asset) record money paid out: the account is debited and the bank
     * credited. Credit-normal accounts (revenue, liability, equity) record money
     * received: the bank is debited and the account credited. Either way the
     * amount rolls up to the parent automatically.
     */
    public function postAccountExpense(AccountExpenseEntry $entry, ?int $recordedByStaffId = null): void
    {
        $bank = (string) $entry->credit_account_code;
        $reference = $entry->reference_no;
        $ledgerDate = $entry->expense_date?->toDateString();
        $memo = $entry->memo !== null && $entry->memo !== '' ? " - {$entry->memo}" : '';

        $account = ChartOfAccount::query()->where('account_code', $entry->account_code)->first();
        $creditNormal = $account !== null && ! $account->isDebitNormal();

        if ($creditNormal) {
            $receivedFrom = $entry->payee_name !== null && $entry->payee_name !== '' ? " from {$entry->payee_name}" : '';

            $this->postEntry(
                'account_transaction',
                $bank,
                (string) $entry->account_code,
                (float) $entry->amount + (float) $entry->vat_amount,
                "Receipt {$reference}{$receivedFrom}{$memo}",
                'account_distribution',
                'account_expense_entries',
                (string) $entry->id,
                $recordedByStaffId,
                $ledgerDate,
            );

            return;
        }

        $paidTo = $entry->payee_name !== null && $entry->payee_name !== '' ? " to {$entry->payee_name}" : '';

        $this->postEntry(
            'account_transaction',
            (string) $entry->account_code,
            $bank,
            (float) $entry->amount,
            "Payment {$reference}{$paidTo}{$memo}",
            'account_distribution',
            'account_expense_entries',
            (string) $entry->id,
            $recordedByStaffId,
            $ledgerDate,
        );

        $vat = (float) $entry->vat_amount;

        if ($vat <= 0) {
            return;
        }

        $vatAccount = (string) (ChartOfAccount::query()
            ->where('is_active', 1)
            ->where(fn ($query) => $query->where('account_name', 'like', '%vat%')
                ->orWhere('account_name', 'like', '%value added tax%'))
            ->orderByRaw("CASE WHEN account_type = 'asset' THEN 0 WHEN account_type = 'liability' THEN 1 ELSE 2 END")
            ->orderBy('account_code')
            ->value('account_code') ?: $entry->account_code);

        $this->postEntry(
            'account_transaction',
            $vatAccount,
            $bank,
            $vat,
            "VAT on payment {$reference}{$paidTo}",
            'account_distribution',
            'account_expense_entries',
            (string) $entry->id,
            $recordedByStaffId,
            $ledgerDate,
        );
    }

    public function postCreditMemo(float $amount, string $creditMemoNumber, ?int $recordedByStaffId = null, ?string $invoiceType = null): AccountLedger
    {
        return $this->postEntry(
            'credit_memo',
            $this->revenueAccountForInvoiceType($invoiceType),
            $this->resolveChartCode((string) config('finance.accounts.accounts_receivable'), 'asset', ['receivable', 'debtor'], 'FINANCE_ACCOUNTS_RECEIVABLE_ACCOUNT', 'Student Fees Receivable'),
            $amount,
            "Credit memo: {$creditMemoNumber}",
            'student_fees',
            'credit_memos',
            $creditMemoNumber,
            $recordedByStaffId,
        );
    }

    public function postPayrollRun(\App\Models\PayrollRun $run, ?int $recordedByStaffId = null): void
    {
        $reference = $run->run_number;
        $period = $run->periodLabel();

        if ((float) $run->total_gross > 0) {
            $this->postEntry(
                'payroll_disbursement',
                $this->resolveChartCode((string) config('finance.accounts.salaries_expense'), 'expense', ['salary', 'payroll', 'staff cost'], 'FINANCE_SALARIES_EXPENSE_ACCOUNT', 'Salaries Expense'),
                $this->resolveChartCode((string) config('finance.accounts.salaries_payable'), 'liability', ['salary payable', 'payroll'], 'FINANCE_SALARIES_PAYABLE_ACCOUNT', 'Salaries Payable'),
                (float) $run->total_gross,
                "Payroll gross - {$period}",
                'payroll',
                'payroll_runs',
                $reference,
                $recordedByStaffId,
            );
        }

        $employerStatutory = max(0, round((float) $run->total_employer_cost - (float) $run->total_gross, 2));

        if ($employerStatutory > 0) {
            $this->postEntry(
                'payroll_disbursement',
                $this->resolveChartCode((string) config('finance.accounts.employer_statutory_expense'), 'expense', ['statutory'], 'FINANCE_EMPLOYER_STATUTORY_ACCOUNT', 'Employer Statutory Expense'),
                $this->resolveChartCode((string) config('finance.accounts.salaries_payable'), 'liability', ['salary payable', 'payroll'], 'FINANCE_SALARIES_PAYABLE_ACCOUNT', 'Salaries Payable'),
                $employerStatutory,
                "Employer statutory - {$period}",
                'payroll',
                'payroll_runs',
                $reference,
                $recordedByStaffId,
            );
        }

        $statutoryCredits = [
            'paye' => (float) $run->total_paye,
            'nssf' => (float) $run->total_nssf,
            'sha' => (float) $run->total_sha,
            'ahl' => (float) $run->total_ahl,
        ];
        $statutoryHints = [
            'paye' => ['paye', 'pay as you earn'],
            'nssf' => ['nssf'],
            'sha' => ['sha'],
            'ahl' => ['housing levy', 'ahl'],
        ];

        foreach ($statutoryCredits as $type => $amount) {
            if ($amount <= 0) {
                continue;
            }

            $payableAccount = $this->resolveChartCode(
                (string) config('finance.accounts.'.$type.'_payable'),
                'liability',
                $statutoryHints[$type],
                'FINANCE_'.strtoupper($type).'_PAYABLE_ACCOUNT',
                strtoupper($type).' Payable',
            );

            $this->postEntry(
                'statutory_remittance',
                $this->resolveChartCode((string) config('finance.accounts.salaries_payable'), 'liability', ['salary payable', 'payroll'], 'FINANCE_SALARIES_PAYABLE_ACCOUNT', 'Salaries Payable'),
                $payableAccount,
                $amount,
                strtoupper($type)." remittance - {$period}",
                'payroll',
                'payroll_runs',
                $reference,
                $recordedByStaffId,
            );
        }

        $netPay = (float) $run->total_net;

        if ($netPay > 0) {
            $this->postEntry(
                'payroll_disbursement',
                $this->resolveChartCode((string) config('finance.accounts.salaries_payable'), 'liability', ['salary payable', 'payroll'], 'FINANCE_SALARIES_PAYABLE_ACCOUNT', 'Salaries Payable'),
                $this->cashAccountFor('bank_transfer'),
                $netPay,
                "Net salaries disbursed - {$period}",
                'payroll',
                'payroll_runs',
                $reference,
                $recordedByStaffId,
            );
        }
    }

    /**
     * @return Collection<int, AccountLedger>
     */
    public function recentEntries(int $limit = 50): Collection
    {
        return AccountLedger::query()
            ->orderByDesc('ledger_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Debit, credit and signed net balance per account, for the given accounts.
     *
     * @param  iterable<ChartOfAccount>  $accounts
     * @return array<string, array{debit: float, credit: float, net: float}>
     */
    public function accountBalanceMap(iterable $accounts): array
    {
        $types = [];
        $codes = [];

        foreach ($accounts as $account) {
            $codes[] = $account->account_code;
            $types[$account->account_code] = $account->account_type;
        }

        if ($codes === []) {
            return [];
        }

        $debits = AccountLedger::query()
            ->select('debit_account_code', DB::raw('SUM(debit_amount) as total'))
            ->whereIn('debit_account_code', $codes)
            ->where('is_reversed', 0)
            ->groupBy('debit_account_code')
            ->pluck('total', 'debit_account_code');

        $credits = AccountLedger::query()
            ->select('credit_account_code', DB::raw('SUM(credit_amount) as total'))
            ->whereIn('credit_account_code', $codes)
            ->where('is_reversed', 0)
            ->groupBy('credit_account_code')
            ->pluck('total', 'credit_account_code');

        $balances = [];

        foreach ($codes as $code) {
            $debit = round((float) ($debits[$code] ?? 0), 2);
            $credit = round((float) ($credits[$code] ?? 0), 2);
            $debitNormal = in_array($types[$code] ?? null, ['asset', 'expense'], true);

            $balances[$code] = [
                'debit' => $debit,
                'credit' => $credit,
                'net' => $debitNormal ? round($debit - $credit, 2) : round($credit - $debit, 2),
            ];
        }

        return $balances;
    }

    /**
     * Signed net balance per active account, honouring reversals and an optional period.
     *
     * Assets and expenses are debit normal (positive when debited); liabilities, equity
     * and revenue are credit normal (positive when credited). This is the figure the
     * financial statements are built from, so a credit to a revenue account shows as
     * income instead of a negative.
     *
     * @return array<string, float>
     */
    public function signedBalances(?string $from = null, ?string $to = null): array
    {
        $accounts = ChartOfAccount::query()->where('is_active', 1)->orderBy('account_code')->get();
        $types = $accounts->pluck('account_type', 'account_code')->all();
        $codes = array_keys($types);

        if ($codes === []) {
            return [];
        }

        $debits = AccountLedger::query()
            ->select('debit_account_code', DB::raw('SUM(debit_amount) as total'))
            ->whereIn('debit_account_code', $codes)
            ->where('is_reversed', 0)
            ->when($from, fn ($q) => $q->where('ledger_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('ledger_date', '<=', $to))
            ->groupBy('debit_account_code')
            ->pluck('total', 'debit_account_code');

        $credits = AccountLedger::query()
            ->select('credit_account_code', DB::raw('SUM(credit_amount) as total'))
            ->whereIn('credit_account_code', $codes)
            ->where('is_reversed', 0)
            ->when($from, fn ($q) => $q->where('ledger_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('ledger_date', '<=', $to))
            ->groupBy('credit_account_code')
            ->pluck('total', 'credit_account_code');

        $balances = [];

        foreach ($codes as $code) {
            $debit = round((float) ($debits[$code] ?? 0), 2);
            $credit = round((float) ($credits[$code] ?? 0), 2);
            $debitNormal = in_array($types[$code] ?? null, ['asset', 'expense'], true);

            $balances[$code] = $debitNormal ? round($debit - $credit, 2) : round($credit - $debit, 2);
        }

        return $balances;
    }

    /**
     * Signed balances (debit-normal positive for asset and expense accounts).
     *
     * @return array<string, float>
     */
    public function accountBalances(?string $from = null, ?string $to = null): array
    {
        return $this->signedBalances($from, $to);
    }

    /**
     * @return array<string, mixed>
     */
    public function trialBalance(): array
    {
        $accounts = ChartOfAccount::query()->where('is_active', 1)->orderBy('account_code')->get();
        $balances = $this->accountBalances();
        $rows = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($accounts as $account) {
            $balance = $balances[$account->account_code] ?? 0.0;
            if (abs($balance) < 0.01) {
                continue;
            }

            $debit = in_array($account->account_type, ['asset', 'expense'], true) && $balance > 0 ? $balance : 0.0;
            $credit = ! in_array($account->account_type, ['asset', 'expense'], true) && $balance > 0 ? $balance : 0.0;

            if ($balance < 0) {
                if (in_array($account->account_type, ['asset', 'expense'], true)) {
                    $credit = abs($balance);
                } else {
                    $debit = abs($balance);
                }
            }

            $rows[] = [
                'account_code' => $account->account_code,
                'account_name' => $account->account_name,
                'account_type' => $account->account_type,
                'debit' => round($debit, 2),
                'credit' => round($credit, 2),
            ];

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        return [
            'rows' => $rows,
            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
        ];
    }

    public function cashAccountFor(?string $paymentMethod): string
    {
        [$configKey, $hints, $environmentVariable, $postingAccountName] = match (strtolower((string) $paymentMethod)) {
            'mpesa', 'mobile_money' => ['cash_mpesa', ['mpesa', 'mobile money'], 'FINANCE_MPESA_CASH_ACCOUNT', 'M-Pesa Cash'],
            'cash' => ['cash_on_hand', ['cash on hand', 'cash'], 'FINANCE_CASH_ACCOUNT', 'Cash on Hand'],
            'card' => ['cash_card', ['card clearing', 'card settlement', 'bank'], 'FINANCE_CARD_CLEARING_ACCOUNT', 'Card Clearing'],
            'helb' => ['helb_clearing', ['helb clearing', 'helb', 'student aid'], 'FINANCE_HELB_CLEARING_ACCOUNT', 'HELB Clearing'],
            'sponsor' => ['sponsor_clearing', ['sponsor clearing', 'sponsor', 'bank'], 'FINANCE_SPONSOR_CLEARING_ACCOUNT', 'Sponsor Clearing'],
            'work_study_credit' => ['work_study_clearing', ['work study clearing', 'work study', 'bank'], 'FINANCE_WORK_STUDY_CLEARING_ACCOUNT', 'Work-study Clearing'],
            'bank', 'bank_transfer', 'cheque', 'eft' => ['cash_bank', ['bank', 'cash'], 'FINANCE_BANK_CASH_ACCOUNT', 'Bank Cash'],
            default => throw ValidationException::withMessages([
                'payment_method' => 'No chart-of-accounts mapping is configured for this payment method.',
            ]),
        };

        return $this->resolveChartCode((string) config('finance.accounts.'.$configKey), 'asset', $hints, $environmentVariable, $postingAccountName);
    }

    public function validateStudentPaymentAccounts(?string $paymentMethod): void
    {
        $this->cashAccountFor($paymentMethod);
        $this->resolveChartCode(
            (string) config('finance.accounts.accounts_receivable'),
            'asset',
            ['receivable', 'debtor'],
            'FINANCE_ACCOUNTS_RECEIVABLE_ACCOUNT',
            'Student Fees Receivable',
        );
    }

    /**
     * Revenue account for an invoice type, so each kind of fee posts to its own line
     * on the income statement instead of everything landing on tuition.
     */
    private function revenueAccountForInvoiceType(?string $invoiceType): string
    {
        [$configKey, $hints, $environmentVariable, $postingAccountName] = match (strtolower((string) $invoiceType)) {
            'application' => ['application_fee_revenue', ['application'], 'FINANCE_APPLICATION_FEE_REVENUE_ACCOUNT', 'Application Fee Revenue'],
            'examination', 'exam', 'supplementary' => ['examination_fee_revenue', ['examination', 'exam'], 'FINANCE_EXAMINATION_FEE_REVENUE_ACCOUNT', 'Examination Fee Revenue'],
            'graduation' => ['graduation_fee_revenue', ['graduation'], 'FINANCE_GRADUATION_FEE_REVENUE_ACCOUNT', 'Graduation Fee Revenue'],
            'tuition', 'semester', 'semester_charges', 'hostel' => ['tuition_revenue', ['tuition', 'fee income', 'school fee'], 'FINANCE_TUITION_REVENUE_ACCOUNT', 'Tuition Fee Revenue'],
            default => ['other_fee_revenue', ['other income', 'other fee', 'miscellaneous'], 'FINANCE_OTHER_FEE_REVENUE_ACCOUNT', 'Other Fee Revenue'],
        };

        return $this->resolveChartCode(
            (string) config('finance.accounts.'.$configKey, config('finance.accounts.tuition_revenue')),
            'revenue',
            $hints,
            $environmentVariable,
            $postingAccountName,
        );
    }

    /**
     * Resolve a posting only to an active leaf account of the expected type.
     * Summary accounts are not used directly: their child balances are rolled up
     * by ChartOfAccountService.
     *
     * @param  list<string>  $nameHints
     */
    public function resolveChartCode(
        string $configured,
        string $type,
        array $nameHints = [],
        string $environmentVariable = 'FINANCE_ACCOUNT',
        ?string $postingAccountName = null,
        bool $alwaysCreatePostingChild = false,
    ): string
    {
        $accounts = $this->chartAccounts();
        $configured = trim($configured);
        $parentCodes = $accounts->pluck('parent_account_code')
            ->filter()
            ->flip();

        $exact = $accounts->firstWhere('account_code', $configured);

        if ($exact !== null) {
            if (! $exact->is_active || $exact->account_type !== $type) {
                $this->throwAccountMappingError(
                    $environmentVariable,
                    $configured,
                    "it is inactive or is not a {$type} account"
                );
            }

            if (isset($parentCodes[$configured]) || $alwaysCreatePostingChild) {
                if ($postingAccountName !== null) {
                    return $this->ensurePostingLeafAccount(
                        $exact,
                        $type,
                        $postingAccountName,
                        $environmentVariable,
                    );
                }

                return $this->leafAccountForParent(
                    $accounts,
                    $parentCodes,
                    $configured,
                    $type,
                    $nameHints,
                    $environmentVariable,
                );
            }

            return (string) $exact->account_code;
        }

        $match = $accounts
            ->filter(fn (ChartOfAccount $account) => $account->account_type === $type
                && $account->is_active
                && ! isset($parentCodes[$account->account_code])
                && $this->nameMatches($account, $nameHints))
            ->values();

        if ($match->count() === 1) {
            return (string) $match->first()->account_code;
        }

        $candidateCodes = $match->pluck('account_code')->take(5)->implode(', ');
        $reason = $match->isEmpty()
            ? 'the configured code does not exist and no active matching leaf account was found'
            : "the configured code does not exist and matching leaf accounts are ambiguous ({$candidateCodes})";

        $this->throwAccountMappingError($environmentVariable, $configured, $reason);
    }

    private function ensurePostingLeafAccount(
        ChartOfAccount $parent,
        string $type,
        string $accountName,
        string $environmentVariable,
    ): string {
        return DB::transaction(function () use ($parent, $type, $accountName, $environmentVariable): string {
            $lockedParent = ChartOfAccount::query()
                ->whereKey($parent->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedParent->is_active || $lockedParent->account_type !== $type) {
                $this->throwAccountMappingError(
                    $environmentVariable,
                    (string) $lockedParent->account_code,
                    "it is inactive or is not a {$type} parent account"
                );
            }

            $existing = ChartOfAccount::query()
                ->where('parent_account_code', $lockedParent->account_code)
                ->where('account_type', $type)
                ->where('account_name', $accountName)
                ->where('is_active', 1)
                ->get()
                ->filter(fn (ChartOfAccount $account) => ! $account->children()->exists())
                ->values();

            if ($existing->count() === 1) {
                return (string) $existing->first()->account_code;
            }

            if ($existing->count() > 1) {
                $systemAccounts = $existing->where('is_system_account', true)->values();

                if ($systemAccounts->count() === 1) {
                    return (string) $systemAccounts->first()->account_code;
                }

                $this->throwAccountMappingError(
                    $environmentVariable,
                    (string) $lockedParent->account_code,
                    'it has multiple posting children named '.$accountName.' ('.$existing->pluck('account_code')->implode(', ').')'
                );
            }

            $accountCode = (new ChartOfAccountService($this))->suggestChildCode($lockedParent);

            return (string) ChartOfAccount::query()->create([
                'account_code' => $accountCode,
                'account_name' => $accountName,
                'account_type' => $type,
                'currency' => $lockedParent->currency ?: ChartOfAccountService::DEFAULT_CURRENCY,
                'parent_account_code' => $lockedParent->account_code,
                'is_active' => true,
                'is_system_account' => true,
            ])->account_code;
        }, 3);
    }

    /**
     * Resolve a configured summary account to a uniquely identifiable active leaf
     * below it; never choose arbitrarily when the parent has several plausible children.
     *
     * @param  Collection<int, ChartOfAccount>  $accounts
     * @param  Collection<string, int>  $parentCodes
     * @param  list<string>  $nameHints
     */
    private function leafAccountForParent(
        Collection $accounts,
        Collection $parentCodes,
        string $parentCode,
        string $type,
        array $nameHints,
        string $environmentVariable,
    ): string {
        $byCode = $accounts->keyBy('account_code');
        $leaves = $accounts->filter(function (ChartOfAccount $account) use ($byCode, $parentCodes, $parentCode, $type): bool {
            if (! $account->is_active || $account->account_type !== $type || isset($parentCodes[$account->account_code])) {
                return false;
            }

            $ancestorCode = $account->parent_account_code;
            $seen = [$account->account_code => true];

            while ($ancestorCode !== null && $ancestorCode !== '' && ! isset($seen[$ancestorCode])) {
                if ($ancestorCode === $parentCode) {
                    return true;
                }

                $seen[$ancestorCode] = true;
                $ancestorCode = $byCode->get($ancestorCode)?->parent_account_code;
            }

            return false;
        })->values();

        $matchingLeaves = $leaves
            ->filter(fn (ChartOfAccount $account) => $this->nameMatches($account, $nameHints))
            ->values();

        if ($matchingLeaves->count() === 1) {
            return (string) $matchingLeaves->first()->account_code;
        }

        if ($matchingLeaves->isEmpty() && $leaves->count() === 1) {
            return (string) $leaves->first()->account_code;
        }

        $candidateCodes = ($matchingLeaves->isNotEmpty() ? $matchingLeaves : $leaves)
            ->pluck('account_code')
            ->take(10)
            ->implode(', ');
        $reason = $candidateCodes === ''
            ? "it is a parent account and has no active {$type} leaf descendants"
            : "it is a parent account with multiple possible {$type} leaf accounts ({$candidateCodes})";

        $this->throwAccountMappingError($environmentVariable, $parentCode, $reason);
    }

    private function throwAccountMappingError(string $environmentVariable, string $configured, string $reason): never
    {
        throw ValidationException::withMessages([
            'chart_of_accounts' => "Finance posting is blocked: account code '{$configured}' {$reason}. Set {$environmentVariable} to an existing active chart account of the required type; a parent account can be used because the system creates a numbered posting child beneath it.",
        ]);
    }

    /**
     * @param  list<string>  $nameHints
     */
    private function nameMatches(ChartOfAccount $account, array $nameHints): bool
    {
        $name = mb_strtolower((string) $account->account_name);

        foreach ($nameHints as $hint) {
            if ($hint !== '' && str_contains($name, mb_strtolower($hint))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection<int, ChartOfAccount>
     */
    private function chartAccounts(): Collection
    {
        return $this->chartAccounts ??= ChartOfAccount::query()->orderBy('account_code')->get();
    }

    private function systemStaffId(): int
    {
        return (int) (\App\Models\Staff::query()->value('id') ?? 1);
    }
}

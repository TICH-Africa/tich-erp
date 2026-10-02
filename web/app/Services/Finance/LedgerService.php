<?php

namespace App\Services\Finance;

use App\Models\AccountLedger;
use App\Models\ChartOfAccount;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LedgerService
{
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
            config('finance.accounts.accounts_receivable'),
            $this->revenueAccountForInvoiceType($invoiceType),
            $amount,
            "Invoice raised: {$invoiceNumber}",
            'student_fees',
            'invoices',
            $invoiceNumber,
            $recordedByStaffId,
        );
    }

    public function postStudentPayment(float $amount, string $paymentNumber, string $paymentMethod, ?int $recordedByStaffId = null, ?string $paymentDate = null): AccountLedger
    {
        return $this->postEntry(
            'student_payment',
            $this->cashAccountFor($paymentMethod),
            config('finance.accounts.accounts_receivable'),
            $amount,
            "Student payment: {$paymentNumber}",
            'student_fees',
            'payments',
            $paymentNumber,
            $recordedByStaffId,
            $paymentDate,
        );
    }

    public function postCreditMemo(float $amount, string $creditMemoNumber, ?int $recordedByStaffId = null, ?string $invoiceType = null): AccountLedger
    {
        return $this->postEntry(
            'credit_memo',
            $this->revenueAccountForInvoiceType($invoiceType),
            config('finance.accounts.accounts_receivable'),
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
                config('finance.accounts.salaries_expense'),
                config('finance.accounts.salaries_payable'),
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
                config('finance.accounts.employer_statutory_expense'),
                config('finance.accounts.salaries_payable'),
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

        foreach ($statutoryCredits as $type => $amount) {
            if ($amount <= 0) {
                continue;
            }

            $payableAccount = config('finance.accounts.'.$type.'_payable');

            $this->postEntry(
                'statutory_remittance',
                config('finance.accounts.salaries_payable'),
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
                config('finance.accounts.salaries_payable'),
                config('finance.accounts.cash_bank'),
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
     * @return array<string, float>
     */
    public function accountBalances(): array
    {
        return $this->signedBalances();
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
        return match (strtolower((string) $paymentMethod)) {
            'mpesa', 'mobile_money' => (string) config('finance.accounts.cash_mpesa'),
            default => (string) config('finance.accounts.cash_bank'),
        };
    }

    /**
     * Revenue account for an invoice type, so each kind of fee posts to its own line
     * on the income statement instead of everything landing on tuition.
     */
    private function revenueAccountForInvoiceType(?string $invoiceType): string
    {
        return match (strtolower((string) $invoiceType)) {
            'application' => (string) config('finance.accounts.application_fee_revenue'),
            'examination', 'exam', 'supplementary' => (string) config('finance.accounts.examination_fee_revenue'),
            'graduation' => (string) config('finance.accounts.graduation_fee_revenue'),
            'tuition', 'semester', 'semester_charges', 'hostel' => (string) config('finance.accounts.tuition_revenue'),
            default => (string) config('finance.accounts.other_fee_revenue', config('finance.accounts.tuition_revenue')),
        };
    }

    private function systemStaffId(): int
    {
        return (int) (\App\Models\Staff::query()->value('id') ?? 1);
    }
}

<?php

namespace App\Services\Finance;

use App\Models\AccountLedger;
use App\Models\ChartOfAccount;
use Illuminate\Support\Collection;

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
    ): AccountLedger {
        return AccountLedger::query()->create([
            'ledger_date' => now()->toDateString(),
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

    public function postInvoiceRaised(float $amount, string $invoiceNumber, ?int $recordedByStaffId = null): AccountLedger
    {
        return $this->postEntry(
            'invoice_raised',
            config('finance.accounts.accounts_receivable'),
            $this->revenueAccountForInvoice($invoiceNumber),
            $amount,
            "Invoice raised: {$invoiceNumber}",
            'student_fees',
            'invoices',
            $invoiceNumber,
            $recordedByStaffId,
        );
    }

    public function postStudentPayment(float $amount, string $paymentNumber, string $paymentMethod, ?int $recordedByStaffId = null): AccountLedger
    {
        $cashAccount = match ($paymentMethod) {
            'mpesa', 'mobile_money' => config('finance.accounts.cash_mpesa'),
            default => config('finance.accounts.cash_bank'),
        };

        return $this->postEntry(
            'student_payment',
            $cashAccount,
            config('finance.accounts.accounts_receivable'),
            $amount,
            "Student payment: {$paymentNumber}",
            'student_fees',
            'payments',
            $paymentNumber,
            $recordedByStaffId,
        );
    }

    public function postCreditMemo(float $amount, string $creditMemoNumber, ?int $recordedByStaffId = null): AccountLedger
    {
        return $this->postEntry(
            'credit_memo',
            $this->revenueAccountForInvoice($creditMemoNumber),
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
                "Payroll gross — {$period}",
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
                "Employer statutory — {$period}",
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
                strtoupper($type)." remittance — {$period}",
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
                "Net salaries disbursed — {$period}",
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
     * @return array<string, float>
     */
    public function accountBalances(): array
    {
        $accounts = ChartOfAccount::query()->where('is_active', 1)->orderBy('account_code')->get();
        $balances = [];

        foreach ($accounts as $account) {
            $code = $account->account_code;
            $debits = (float) AccountLedger::query()->where('debit_account_code', $code)->sum('debit_amount');
            $credits = (float) AccountLedger::query()->where('credit_account_code', $code)->sum('credit_amount');

            $balances[$code] = match ($account->account_type) {
                'asset', 'expense' => round($debits - $credits, 2),
                default => round($credits - $debits, 2),
            };
        }

        return $balances;
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

    private function revenueAccountForInvoice(string $invoiceNumber): string
    {
        return config('finance.accounts.tuition_revenue');
    }

    private function systemStaffId(): int
    {
        return (int) (\App\Models\Staff::query()->value('id') ?? 1);
    }
}

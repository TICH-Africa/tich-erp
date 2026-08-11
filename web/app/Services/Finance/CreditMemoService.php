<?php

namespace App\Services\Finance;

use App\Models\CreditMemo;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class CreditMemoService
{
    public function __construct(
        protected LedgerService $ledger,
        protected StudentAccountService $accounts,
    ) {}

    public function issue(Invoice $invoice, float $amount, string $reason, int $issuedByStaffId): CreditMemo
    {
        return DB::transaction(function () use ($invoice, $amount, $reason, $issuedByStaffId) {
            $invoice->loadMissing('studentAccount');

            $creditAmount = round(min($amount, (float) $invoice->balance), 2);
            abort_if($creditAmount <= 0, 422, 'Nothing to credit on this invoice.');

            $memo = CreditMemo::query()->create([
                'credit_memo_number' => $this->nextNumber(),
                'invoice_id' => $invoice->id,
                'student_account_id' => $invoice->student_account_id,
                'student_id' => $invoice->student_id,
                'amount' => $creditAmount,
                'reason' => $reason,
                'status' => 'issued',
                'issued_by' => $issuedByStaffId,
                'issued_at' => now(),
            ]);

            $newBalance = round((float) $invoice->balance - $creditAmount, 2);

            $invoice->update([
                'balance' => max($newBalance, 0),
                'status' => $newBalance <= 0 ? 'paid' : ($invoice->status === 'overdue' ? 'overdue' : 'partial'),
            ]);

            $this->ledger->postCreditMemo($creditAmount, $memo->credit_memo_number, $issuedByStaffId);
            $this->accounts->recalculate($invoice->studentAccount);

            return $memo->fresh(['invoice', 'student.applicant', 'issuer']);
        });
    }

    private function nextNumber(): string
    {
        $sequence = CreditMemo::query()->count() + 1;

        return 'CM-'.now()->format('Ymd').'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}

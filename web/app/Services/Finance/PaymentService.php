<?php

namespace App\Services\Finance;

use App\Mail\PaymentConfirmationMail;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\ModuleMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentService
{
    public function __construct(
        protected StudentAccountService $accounts,
        protected LedgerService $ledger,
    ) {}

    /**
     * @param  array{amount: float, payment_method: string, payment_reference?: string|null, transaction_channel_ref?: string|null, payment_date?: string|null}  $data
     */
    public function recordPayment(Invoice $invoice, array $data, int $recordedByStaffId, bool $sendConfirmation = true): Payment
    {
        return DB::transaction(function () use ($invoice, $data, $recordedByStaffId, $sendConfirmation) {
            $invoice->loadMissing(['studentAccount', 'student.applicant', 'student.user']);
            $amount = round(min((float) $data['amount'], (float) $invoice->balance), 2);

            abort_if($amount <= 0, 422, 'Payment amount must be greater than zero.');

            $payment = Payment::query()->create([
                'payment_number' => $this->nextPaymentNumber(),
                'invoice_id' => $invoice->id,
                'student_account_id' => $invoice->student_account_id,
                'student_id' => $invoice->student_id,
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'amount' => $amount,
                'payment_method' => $data['payment_method'],
                'payment_reference' => $data['payment_reference'] ?? null,
                'transaction_channel_ref' => $data['transaction_channel_ref'] ?? null,
                'is_reconciled' => 1,
                'reconciled_by' => $recordedByStaffId,
                'reconciled_at' => now(),
                'recorded_by' => $recordedByStaffId,
            ]);

            $newPaid = round((float) $invoice->amount_paid + $amount, 2);
            $newBalance = round((float) $invoice->amount - $newPaid, 2);

            $invoice->update([
                'amount_paid' => $newPaid,
                'balance' => max($newBalance, 0),
                'status' => $newBalance <= 0 ? 'paid' : 'partial',
                'payment_gateway_ref' => $data['transaction_channel_ref'] ?? $invoice->payment_gateway_ref,
            ]);

            $this->ledger->postStudentPayment($amount, $payment->payment_number, $payment->payment_method, $recordedByStaffId);
            $this->accounts->recalculate($invoice->studentAccount);

            if ($sendConfirmation) {
                $this->sendConfirmation($payment->fresh(['invoice', 'student.applicant', 'student.user']));
            }

            return $payment;
        });
    }

    public function initiateMpesaPayment(Invoice $invoice, float $amount, string $phoneNumber): array
    {
        abort_unless($invoice->isPayable(), 422, 'This invoice is not open for payment.');
        abort_unless(config('finance.mpesa.enabled'), 422, 'M-Pesa payments are not enabled yet.');

        $reference = 'MPESA-'.strtoupper(substr(sha1($invoice->id.now()->timestamp), 0, 12));

        return [
            'status' => 'pending',
            'reference' => $reference,
            'amount' => round(min($amount, (float) $invoice->balance), 2),
            'phone' => $phoneNumber,
            'message' => 'M-Pesa STK push would be initiated here when live credentials are configured.',
        ];
    }

    private function sendConfirmation(Payment $payment): void
    {
        $email = $payment->student?->applicant?->email
            ?? $payment->student?->user?->email;

        if (! $email) {
            return;
        }

        try {
            ModuleMail::send(ModuleMail::FINANCE, $email, new PaymentConfirmationMail($payment));
        } catch (Throwable $e) {
            Log::error('Failed to send payment confirmation email', [
                'payment_id' => $payment->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function nextPaymentNumber(): string
    {
        $latestId = (int) Payment::query()->max('id');

        return 'PAY-'.now()->format('Ymd').'-'.str_pad((string) ($latestId + 1), 5, '0', STR_PAD_LEFT);
    }
}

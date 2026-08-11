<?php

namespace App\Services\Finance;

use App\Mail\PaymentConfirmationMail;
use App\Models\Invoice;
use App\Models\MpesaStkRequest;
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
        protected MpesaSettingsService $mpesaSettings,
        protected MpesaDarajaService $mpesaDaraja,
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

    public function initiateMpesaPayment(Invoice $invoice, float $amount, string $phoneNumber, int $studentId): MpesaStkRequest
    {
        abort_unless($invoice->isPayable(), 422, 'This invoice is not open for payment.');
        abort_unless($this->mpesaSettings->isEnabled(), 422, 'M-Pesa payments are not enabled. Configure them under Finance → M-Pesa settings.');

        $payAmount = round(min($amount, (float) $invoice->balance), 2);
        abort_if($payAmount <= 0, 422, 'Nothing left to pay on this invoice.');

        $normalizedPhone = $this->mpesaDaraja->normalizePhone($phoneNumber);

        $recentPending = MpesaStkRequest::query()
            ->where('invoice_id', $invoice->id)
            ->where('status', MpesaStkRequest::STATUS_PENDING)
            ->where('created_at', '>=', now()->subMinutes(3))
            ->exists();

        abort_if($recentPending, 422, 'An M-Pesa prompt is already pending for this invoice. Check your phone or wait a moment.');

        $accountReference = substr($this->mpesaSettings->accountReferencePrefix().'-'.$invoice->invoice_number, 0, 12);
        $description = 'TICH '.str_replace('_', ' ', $invoice->invoice_type);

        $stkRequest = MpesaStkRequest::query()->create([
            'invoice_id' => $invoice->id,
            'student_id' => $studentId,
            'amount' => $payAmount,
            'phone' => $normalizedPhone,
            'account_reference' => $accountReference,
            'status' => MpesaStkRequest::STATUS_PENDING,
        ]);

        try {
            $response = $this->mpesaDaraja->stkPush(
                $payAmount,
                $normalizedPhone,
                $accountReference,
                $description,
            );

            $stkRequest->update([
                'merchant_request_id' => $response['merchant_request_id'],
                'checkout_request_id' => $response['checkout_request_id'],
            ]);

            $invoice->update([
                'payment_gateway_ref' => $response['checkout_request_id'],
            ]);
        } catch (Throwable $e) {
            $stkRequest->update([
                'status' => MpesaStkRequest::STATUS_FAILED,
                'result_desc' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }

        return $stkRequest->fresh(['invoice']);
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

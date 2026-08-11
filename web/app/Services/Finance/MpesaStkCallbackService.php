<?php

namespace App\Services\Finance;

use App\Models\Invoice;
use App\Models\MpesaStkRequest;
use App\Models\Payment;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MpesaStkCallbackService
{
    public function __construct(
        protected PaymentService $payments,
        protected MpesaDarajaService $daraja,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload): void
    {
        $callback = data_get($payload, 'Body.stkCallback');

        if (! is_array($callback)) {
            Log::warning('M-Pesa callback missing stkCallback body', ['payload' => $payload]);

            return;
        }

        $checkoutRequestId = (string) ($callback['CheckoutRequestID'] ?? '');

        if ($checkoutRequestId === '') {
            return;
        }

        $stkRequest = MpesaStkRequest::query()
            ->where('checkout_request_id', $checkoutRequestId)
            ->first();

        if (! $stkRequest) {
            Log::warning('M-Pesa callback for unknown checkout request', ['checkout_request_id' => $checkoutRequestId]);

            return;
        }

        if ($stkRequest->isTerminal()) {
            return;
        }

        $this->applyResult($stkRequest, $callback, $payload);
    }

    public function reconcilePending(MpesaStkRequest $stkRequest): MpesaStkRequest
    {
        if ($stkRequest->isTerminal() || ! $stkRequest->checkout_request_id) {
            return $stkRequest;
        }

        $query = $this->daraja->stkQuery($stkRequest->checkout_request_id);
        $resultCode = $query['result_code'];

        if ($resultCode === null) {
            if ($stkRequest->created_at && $stkRequest->created_at->lt(now()->subMinutes(30))) {
                $stkRequest->update([
                    'status' => MpesaStkRequest::STATUS_TIMEOUT,
                    'result_desc' => 'Payment timed out before confirmation.',
                    'completed_at' => now(),
                ]);
            }

            return $stkRequest->fresh();
        }

        $callback = [
            'CheckoutRequestID' => $stkRequest->checkout_request_id,
            'MerchantRequestID' => $stkRequest->merchant_request_id,
            'ResultCode' => $resultCode,
            'ResultDesc' => $query['result_desc'] ?? 'STK query result',
        ];

        $this->applyResult($stkRequest, $callback, ['source' => 'stk_query']);

        return $stkRequest->fresh();
    }

    /**
     * @param  array<string, mixed>  $callback
     * @param  array<string, mixed>  $rawPayload
     */
    private function applyResult(MpesaStkRequest $stkRequest, array $callback, array $rawPayload): void
    {
        $resultCode = (int) ($callback['ResultCode'] ?? -1);
        $resultDesc = (string) ($callback['ResultDesc'] ?? 'Unknown result');

        DB::transaction(function () use ($stkRequest, $callback, $rawPayload, $resultCode, $resultDesc) {
            $locked = MpesaStkRequest::query()->lockForUpdate()->find($stkRequest->id);

            if (! $locked || $locked->isTerminal()) {
                return;
            }

            $locked->update([
                'callback_payload' => $rawPayload,
                'result_code' => $resultCode,
                'result_desc' => $resultDesc,
            ]);

            if ($resultCode !== 0) {
                $status = $this->failureStatus($resultCode);
                $locked->update([
                    'status' => $status,
                    'completed_at' => now(),
                ]);

                return;
            }

            $metadata = $this->parseCallbackMetadata($callback);
            $receipt = (string) ($metadata['MpesaReceiptNumber'] ?? '');
            $amount = isset($metadata['Amount']) ? (float) $metadata['Amount'] : (float) $locked->amount;

            if ($receipt === '' && $locked->checkout_request_id) {
                $receipt = 'STK-'.$locked->checkout_request_id;
            }

            if ($receipt !== '' && Payment::query()->where('payment_reference', $receipt)->exists()) {
                $locked->update([
                    'status' => MpesaStkRequest::STATUS_SUCCESS,
                    'mpesa_receipt_number' => $receipt,
                    'completed_at' => now(),
                    'result_desc' => 'Duplicate callback ignored — payment already recorded.',
                ]);

                return;
            }

            $invoice = Invoice::query()->lockForUpdate()->find($locked->invoice_id);

            if (! $invoice || ! $invoice->isPayable()) {
                $locked->update([
                    'status' => MpesaStkRequest::STATUS_FAILED,
                    'completed_at' => now(),
                    'result_desc' => 'Invoice is no longer payable.',
                ]);

                return;
            }

            $payment = $this->payments->recordPayment($invoice, [
                'amount' => min($amount, (float) $invoice->balance),
                'payment_method' => 'mpesa',
                'payment_reference' => $receipt !== '' ? $receipt : null,
                'transaction_channel_ref' => $locked->checkout_request_id,
            ], $this->systemStaffId());

            $locked->update([
                'status' => MpesaStkRequest::STATUS_SUCCESS,
                'mpesa_receipt_number' => $receipt !== '' ? $receipt : null,
                'payment_id' => $payment->id,
                'completed_at' => now(),
            ]);

            $invoice->update([
                'payment_gateway_ref' => $locked->checkout_request_id,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $callback
     * @return array<string, mixed>
     */
    private function parseCallbackMetadata(array $callback): array
    {
        $items = data_get($callback, 'CallbackMetadata.Item', []);
        $metadata = [];

        foreach ($items as $item) {
            if (! is_array($item) || ! isset($item['Name'])) {
                continue;
            }

            $metadata[(string) $item['Name']] = $item['Value'] ?? null;
        }

        return $metadata;
    }

    private function failureStatus(int $resultCode): string
    {
        return match ($resultCode) {
            1032 => MpesaStkRequest::STATUS_CANCELLED,
            1037 => MpesaStkRequest::STATUS_TIMEOUT,
            default => MpesaStkRequest::STATUS_FAILED,
        };
    }

    private function systemStaffId(): int
    {
        return (int) (Staff::query()->value('id') ?? 1);
    }
}

<?php

namespace App\Services\Procurement;

use App\Models\ProcurementInvoice;
use App\Models\ProcurementPayment;
use App\Models\ThreeWayMatch;
use App\Models\Discrepancy;
use App\Models\MpesaStkRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PaymentVerificationService
{
    public const MPESA_THRESHOLD = 70000.00;

    public function routeToFinance(ProcurementInvoice $invoice, string $paymentMethod, string $reference = null): ProcurementPayment
    {
        return DB::transaction(function () use ($invoice, $paymentMethod, $reference) {
            $retention = $invoice->retention_amount ?? 0;
            $payable = $invoice->balance - $retention;

            $payment = ProcurementPayment::create([
                'invoice_id' => $invoice->id,
                'payment_number' => $this->generatePaymentNumber(),
                'supplier_id' => $invoice->supplier_id,
                'amount' => $payable,
                'retention_amount' => $retention,
                'released_amount' => $payable,
                'payment_method' => $paymentMethod,
                'payment_reference' => $reference ?? '',
                'transaction_channel_ref' => '',
                'status' => 'pending',
                'recorded_by' => Auth::id(),
                'payment_date' => now(),
            ]);

            if ($paymentMethod === 'mpesa' && $payable <= self::MPESA_THRESHOLD) {
                $stkRequest = MpesaStkRequest::create([
                    'invoice_id' => $invoice->id,
                    'amount' => $payable,
                    'phone' => $invoice->supplier->phone,
                    'account_reference' => $payment->payment_number,
                    'status' => 'pending',
                ]);
                $payment->mpesa_stk_request_id = $stkRequest->id;
                $payment->transaction_channel_ref = $stkRequest->id;
                $payment->status = 'stk_pending';
                $payment->save();
            }

            $invoice->status = 'payment_processing';
            $invoice->save();

            return $payment;
        });
    }

    public function processCallback(MpesaStkRequest $stkRequest, string $resultCode, string $resultDesc): bool
    {
        return DB::transaction(function () use ($stkRequest, $resultCode, $resultDesc) {
            $payment = ProcurementPayment::where('mpesa_stk_request_id', $stkRequest->id)->first();

            if ($resultCode === '0') {
                $stkRequest->update(['status' => 'completed', 'result_code' => $resultCode, 'result_desc' => $resultDesc]);
                if ($payment) {
                    $payment->update(['status' => 'success', 'payment_reference' => $stkRequest->CheckoutRequestID]);
                    $invoice = $payment->invoice;
                    $invoice->amount_paid += $payment->amount;
                    $invoice->balance = max(0, $invoice->balance - $payment->amount);
                    $invoice->paid_at = now();
                    $invoice->save();
                }
                return true;
            }

            $stkRequest->update(['status' => 'failed', 'result_code' => $resultCode, 'result_desc' => $resultDesc]);
            if ($payment) {
                $payment->update(['status' => 'failed', 'result_desc' => $resultDesc]);
            }
            return false;
        });
    }

    public function releaseHoldback(ProcurementPayment $payment, float $amount): ProcurementPayment
    {
        return DB::transaction(function () use ($payment, $amount) {
            if ($amount > $payment->retention_amount) {
                throw new \InvalidArgumentException('Release amount exceeds retention balance');
            }
            $payment->retention_amount -= $amount;
            $payment->released_amount += $amount;
            $payment->status = 'success';
            $payment->save();

            $invoice = $payment->invoice;
            $invoice->released_amount += $amount;
            if ($invoice->released_amount >= $invoice->total_amount) {
                $invoice->status = 'paid';
            }
            $invoice->save();

            return $payment;
        });
    }

    public function getPaymentSummary(): array
    {
        return [
            'total_pending' => ProcurementPayment::pending()->sum('amount'),
            'total_success' => ProcurementPayment::success()->sum('amount'),
            'total_mpesa' => ProcurementPayment::mpesa()->sum('amount'),
            'total_bank' => ProcurementPayment::bank()->sum('amount'),
        ];
    }

    private function generatePaymentNumber(): string
    {
        $year = now()->format('Y');
        $count = ProcurementPayment::whereYear('created_at', $year)->count() + 1;
        return "PAY-{$year}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}

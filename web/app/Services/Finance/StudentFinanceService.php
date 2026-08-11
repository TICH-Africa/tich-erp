<?php

namespace App\Services\Finance;

use App\Models\Student;
use App\Models\Finance\StudentAccount;
use App\Models\Finance\Invoice;
use App\Models\Finance\Payment;
use App\Models\Finance\Receipt;
use App\Models\Finance\FinancialAdjustment;
use App\Models\Finance\InstallmentPlan;
use App\Models\Finance\Refund;
use Illuminate\Support\Facades\DB;

class StudentFinanceService
{
    public function openAccount(int $studentId, int $academicYearId): StudentAccount
    {
        return StudentAccount::openForStudent($studentId, $academicYearId);
    }

    public function recalculateAccount(StudentAccount $account): StudentAccount
    {
        $totalChargeable = $account->invoices()
            ->whereIn('status', ['issued', 'partial', 'overdue'])
            ->sum('balance');

        $totalPaid = $account->payments()
            ->where('status', 'SUCCESS')
            ->sum('amount');

        $totalAdjustments = $account->adjustments()
            ->where('status', 'approved')
            ->sum('amount');

        $creditBalance = max(0, $totalPaid - $totalChargeable + $totalAdjustments);
        $outstandingBalance = max(0, $totalChargeable - $totalPaid - $totalAdjustments);

        $account->update([
            'total_chargeable' => $totalChargeable,
            'total_paid' => $totalPaid,
            'outstanding_balance' => $outstandingBalance,
            'credit_balance' => $creditBalance,
            'is_cleared' => $outstandingBalance <= 0,
            'cleared_at' => $outstandingBalance <= 0 ? now() : null,
            'last_payment_date' => $account->payments()
                ->where('status', 'SUCCESS')
                ->max('payment_date'),
        ]);

        return $account->fresh();
    }

    public function allocatePayment(Payment $payment): void
    {
        if ($payment->status !== 'SUCCESS') {
            return;
        }

        $remaining = (float) $payment->amount;
        $invoices = Invoice::where('student_account_id', $payment->student_account_id)
            ->whereIn('status', ['issued', 'partial', 'overdue'])
            ->orderByDesc('issue_date')
            ->get();

        foreach ($invoices as $invoice) {
            if ($remaining <= 0) {
                break;
            }

            $allocate = min($remaining, (float) $invoice->balance);
            if ($allocate <= 0) {
                continue;
            }

            DB::transaction(function () use ($invoice, $allocate, $payment) {
                $invoice->increment('amount_paid', $allocate);
                $invoice->decrement('balance', $allocate);

                if ((float) $invoice->balance <= 0) {
                    $invoice->update(['status' => 'paid']);
                } elseif ($invoice->status === 'issued') {
                    $invoice->update(['status' => 'partial']);
                }

                $payment->allocations()->create([
                    'invoice_id' => $invoice->id,
                    'allocated_amount' => $allocate,
                ]);
            });

            $remaining -= $allocate;
        }

        $this->recalculateAccount($payment->studentAccount);
    }

    public function issueReceipt(Payment $payment): Receipt
    {
        $receiptNumber = 'RCP-' . now()->format('Y') . '-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT);

        return Receipt::create([
            'receipt_number' => $receiptNumber,
            'payment_id' => $payment->id,
            'invoice_id' => $payment->invoice_id,
            'student_account_id' => $payment->student_account_id,
            'student_id' => $payment->student_id,
            'amount' => $payment->amount,
            'payment_method' => $payment->payment_method,
            'payment_reference' => $payment->payment_reference,
            'issued_by' => $payment->recorded_by,
        ]);
    }

    public function approveAdjustment(FinancialAdjustment $adjustment, int $approvedBy): FinancialAdjustment
    {
        if ($adjustment->status !== 'pending') {
            return $adjustment;
        }

        $adjustment->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        if ($adjustment->studentAccount) {
            $this->recalculateAccount($adjustment->studentAccount);
        }

        return $adjustment->fresh();
    }

    public function approveRefund(Refund $refund, int $approvedBy): Refund
    {
        if ($refund->status !== 'pending') {
            return $refund;
        }

        if ($refund->requested_by === $approvedBy) {
            return $refund;
        }

        $refund->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        return $refund->fresh();
    }

    public function processRefund(Refund $refund, int $processedBy): Refund
    {
        if (! in_array($refund->status, ['pending', 'approved'])) {
            return $refund;
        }

        DB::transaction(function () use ($refund, $processedBy) {
            $payment = $refund->payment;

            if ($payment && $payment->status === 'SUCCESS') {
                $payment->update(['status' => 'REFUNDED']);
            }

            $refund->update([
                'status' => 'processed',
                'processed_by' => $processedBy,
                'processed_at' => now(),
            ]);

            if ($refund->studentAccount) {
                $this->recalculateAccount($refund->studentAccount);
            }
        });

        return $refund->fresh();
    }
}

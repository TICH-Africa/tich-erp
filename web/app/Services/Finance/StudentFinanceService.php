<?php

namespace App\Services\Finance;

use App\Models\Student;
use App\Models\Finance\StudentAccount;
use App\Models\Finance\Invoice;
use App\Models\Finance\Payment;
use App\Models\Finance\Receipt;
use App\Models\Finance\FinancialAdjustment;
use App\Models\Finance\InstallmentPlan;
use App\Models\Finance\InstallmentPlanItem;
use App\Models\Finance\PaymentMilestone;
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
        return DB::transaction(function () use ($adjustment, $approvedBy) {
            /** @var FinancialAdjustment $adjustment */
            $adjustment = FinancialAdjustment::query()->whereKey($adjustment->id)->lockForUpdate()->firstOrFail();

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
        }, 3);
    }

    public function approveRefund(Refund $refund, int $approvedBy): Refund
    {
        return DB::transaction(function () use ($refund, $approvedBy) {
            /** @var Refund $refund */
            $refund = Refund::query()->whereKey($refund->id)->lockForUpdate()->firstOrFail();

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
        }, 3);
    }

    public function processRefund(Refund $refund, int $processedBy): Refund
    {
        return DB::transaction(function () use ($refund, $processedBy) {
            /** @var Refund $refund */
            $refund = Refund::query()->whereKey($refund->id)->lockForUpdate()->firstOrFail();

            if (! in_array($refund->status, ['pending', 'approved'], true)) {
                return $refund;
            }

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

            return $refund->fresh();
        }, 3);
    }

    public function autoGenerateInstallmentPlan($payment, int $recordedByStaffId): ?InstallmentPlan
    {
        $invoice = $payment->invoice;
        if (! $invoice) {
            return null;
        }

        $account = $payment->studentAccount ?? StudentAccount::find($payment->student_account_id);
        if (! $account) {
            return null;
        }

        return DB::transaction(function () use ($payment, $recordedByStaffId, $invoice, $account) {
            $existingPlan = InstallmentPlan::query()
                ->where('student_account_id', $account->id)
                ->where('invoice_id', $invoice->id)
                ->lockForUpdate()
                ->first();

            if ($existingPlan) {
                $this->updatePlanProgress($existingPlan, $account);

                return $existingPlan;
            }

            $semester = $invoice->semester;
            $semesterId = $invoice->semester_id;
            $academicYearId = $semester?->academic_year_id ?? ($account->academic_year_id ?? null);

            $totalAmount = max(0, (float) $invoice->balance);

            $plan = InstallmentPlan::create([
                'student_account_id' => $account->id,
                'student_id' => $payment->student_id,
                'invoice_id' => $invoice->id,
                'semester_id' => $semesterId,
                'academic_year_id' => $academicYearId,
                'plan_number' => 'INST-'.now()->format('Ym').'-'.str_pad((string) $account->id, 4, '0', STR_PAD_LEFT),
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'remaining_amount' => $totalAmount,
                'status' => 'active',
            ]);

            $milestones = [
                ['milestone_type' => 'registration', 'percentage' => 50, 'due_offset_days' => 0],
                ['milestone_type' => 'mid_semester', 'percentage' => 75, 'due_offset_days' => 60],
                ['milestone_type' => 'final', 'percentage' => 100, 'due_offset_days' => 120],
            ];

            $paidSoFar = 0;

            foreach ($milestones as $m) {
                $milestoneAmount = round($totalAmount * ($m['percentage'] / 100), 2);
                $milestonePaid = min($payment->amount, max(0, $milestoneAmount - $paidSoFar));
                $paidSoFar += $milestonePaid;

                $status = match (true) {
                    $milestonePaid >= $milestoneAmount && $milestoneAmount > 0 => 'paid',
                    $milestonePaid > 0 && $milestonePaid < $milestoneAmount => 'partial',
                    $milestoneAmount <= 0 => 'paid',
                    default => 'pending',
                };

                PaymentMilestone::create([
                    'student_account_id' => $account->id,
                    'student_id' => $payment->student_id,
                    'invoice_id' => $invoice->id,
                    'milestone_type' => $m['milestone_type'],
                    'percentage' => $m['percentage'],
                    'milestone_amount' => $milestoneAmount,
                    'paid_amount' => $milestonePaid,
                    'status' => $status,
                    'due_date' => now()->addDays($m['due_offset_days'])->toDateString(),
                    'paid_at' => $milestonePaid > 0 ? now() : null,
                    'recorded_by' => $recordedByStaffId,
                ]);
            }

            InstallmentPlanItem::create([
                'installment_plan_id' => $plan->id,
                'installment_number' => 1,
                'amount' => $totalAmount,
                'due_date' => now()->addDays(30)->toDateString(),
                'status' => 'pending',
                'paid_amount' => $payment->amount,
                'paid_at' => now(),
            ]);

            $this->updatePlanProgress($plan, $account);

            return $plan->fresh(['student', 'invoice', 'items', 'milestones']);
        }, 3);
    }

     public function updatePlanProgress(InstallmentPlan $plan, StudentAccount $account): void
    {
        $query = Payment::query()
            ->where('student_id', $plan->student_id)
            ->where('status', 'SUCCESS')
            ->join('invoices as i', 'payments.invoice_id', '=', 'i.id');

        if ($plan->semester_id) {
            $query->where('i.semester_id', $plan->semester_id);
        }

        $totalPaid = (float) $query->sum('payments.amount');

        $plan->update([
            'paid_amount' => $totalPaid,
            'remaining_amount' => max(0, (float) $plan->total_amount - $totalPaid),
            'status' => $totalPaid >= $plan->total_amount ? 'completed' : ($totalPaid > 0 ? 'active' : 'pending'),
        ]);

        if ($plan->milestones) {
            $milestones = $plan->milestones->sortBy('percentage');
            foreach ($milestones as $milestone) {
                $milestoneAmount = (float) $milestone->milestone_amount;
                $milestonePaid = min($totalPaid, $milestoneAmount);
                $milestone->update([
                    'paid_amount' => $milestonePaid,
                    'status' => $milestonePaid >= $milestoneAmount
                        ? ($milestoneAmount > 0 ? 'paid' : 'pending')
                        : ($milestonePaid > 0 ? 'partial' : 'pending'),
                    'paid_at' => $milestonePaid > 0 ? now() : null,
                ]);
            }
        }
    }
}

<?php

namespace App\Services\Finance;

use App\Models\Student;
use App\Support\SchemaProbe;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * What a student owes, measured on the bills and the money rather than on the figures cached on
 * the account row. Both ways payment arrives - Finance typing it in and Safaricom booking a
 * paybill receipt - write a payment and move the invoice, so the portal follows them the same
 * second even when an account row was never recalculated.
 */
class StudentFeeBalanceService
{
    /** Bills that ask the student for nothing, so they are not charged to them either. */
    private const UNCHARGED_STATUSES = ['waived', 'void', 'cancelled', 'draft'];

    /** Receipts that never became money, so they cannot reduce a balance. */
    private const UNCOUNTED_PAYMENTS = ['pending', 'initiated', 'failed', 'cancelled', 'canceled', 'reversed', 'void', 'refunded'];

    /** Bills that still want paying. */
    private const OPEN_STATUSES = ['issued', 'partial', 'overdue'];

    /**
     * @return array<string, float|int|bool>
     */
    public function position(Student $student): array
    {
        $bills = $this->bills($student);
        $payments = $this->payments($student);
        $credits = $this->creditColumns($student);
        $waivers = $this->approvedAmount($this->adjustments($student));

        $billed = round((float) $bills->sum('amount'), 2);
        $paid = round((float) $payments->sum('amount'), 2);
        $cover = round($credits + $waivers, 2);

        return [
            'billed' => $billed,
            'paid' => $paid,
            'credits' => round($credits, 2),
            'waivers' => round($waivers, 2),
            'outstanding' => max(round($billed - $paid - $cover, 2), 0.0),
            'excess' => max(round($paid + $cover - $billed, 2), 0.0),
            'payable_invoice_count' => $bills
                ->filter(fn (object $bill) => in_array((string) $bill->status, self::OPEN_STATUSES, true) && (float) $bill->balance > 0)
                ->count(),
            'is_cleared' => $billed > 0 && $billed - $paid - $cover <= 0,
        ];
    }

    /**
     * The fee accounts table as the student's own ledger: each year carries the bills and receipts
     * linked to it, so a term whose cached balance was never recomputed still reads what it owes
     * now. A bill raised without a year goes on the newest row, keeping the table in step with the
     * card above it.
     *
     * @param  Collection<int, object>  $accounts
     * @return Collection<int, object>
     */
    public function withLiveBalances(Student $student, Collection $accounts): Collection
    {
        if ($accounts->isEmpty()) {
            return $accounts;
        }

        $newestId = (int) $accounts->first()->id;

        $bills = $this->bills($student)->groupBy(fn (object $bill) => (int) ($bill->student_account_id ?: $newestId));
        $payments = $this->payments($student)->groupBy(fn (object $payment) => (int) ($payment->student_account_id ?: $newestId));
        $adjustments = $this->adjustments($student)->groupBy(fn (object $row) => (int) ($row->student_account_id ?: $newestId));

        foreach ($accounts as $account) {
            $id = (int) $account->id;
            $chargeable = round((float) $bills->get($id, collect())->sum('amount'), 2);
            $paid = round((float) $payments->get($id, collect())->sum('amount'), 2);
            $cover = round($this->rowCreditColumns($account) + $this->approvedAmount($adjustments->get($id, collect())), 2);
            $outstanding = max(round($chargeable - $paid - $cover, 2), 0.0);

            $account->total_chargeable = $chargeable;
            $account->total_paid = $paid;
            $account->outstanding_balance = $outstanding;
            $account->credit_balance = max(round($paid + $cover - $chargeable, 2), 0.0);
            $account->is_cleared = $chargeable > 0 && $outstanding <= 0;
        }

        return $accounts;
    }

    /**
     * Scholarship, HELB, sponsor and work-study cover on every one of the student's year rows,
     * because cover on a past year is still cover.
     */
    public function creditColumns(Student $student): float
    {
        return $this->accountRows($student)
            ->sum(fn (object $account) => $this->rowCreditColumns($account));
    }

    /**
     * @return Collection<int, object>
     */
    private function accountRows(Student $student): Collection
    {
        if (! SchemaProbe::hasTable('student_accounts')) {
            return collect();
        }

        return DB::table('student_accounts')->where('student_id', $student->id)->get();
    }

    private function rowCreditColumns(object $account): float
    {
        return (float) ($account->scholarship_amount ?? 0)
            + (float) ($account->helb_amount ?? 0)
            + (float) ($account->sponsor_amount ?? 0)
            + (float) ($account->work_study_credit ?? 0);
    }

    /**
     * @return Collection<int, object>
     */
    private function bills(Student $student): Collection
    {
        return DB::table('invoices')
            ->where('student_id', $student->id)
            ->whereNotIn('status', self::UNCHARGED_STATUSES)
            ->get(['amount', 'balance', 'status', 'student_account_id']);
    }

    /**
     * @return Collection<int, object>
     */
    private function payments(Student $student): Collection
    {
        return DB::table('payments')
            ->where('student_id', $student->id)
            ->get(['amount', 'status', 'student_account_id'])
            ->reject(fn (object $payment) => in_array(strtolower((string) $payment->status), self::UNCOUNTED_PAYMENTS, true));
    }

    /**
     * Approved waivers, bursaries and scholarships raised against the student or any of their
     * year rows. A request still waiting on Finance takes nothing off the balance.
     *
     * @return Collection<int, object>
     */
    private function adjustments(Student $student): Collection
    {
        if (! SchemaProbe::hasTable('financial_adjustments')) {
            return collect();
        }

        $accountIds = $this->accountRows($student)->pluck('id');

        return DB::table('financial_adjustments')
            ->where('status', 'approved')
            ->where(function ($query) use ($student, $accountIds) {
                $query->where('student_id', $student->id);

                if ($accountIds->isNotEmpty()) {
                    $query->orWhereIn('student_account_id', $accountIds->all());
                }
            })
            ->get(['amount', 'status', 'student_account_id']);
    }

    private function approvedAmount(Collection $rows): float
    {
        return round((float) $rows->sum('amount'), 2);
    }
}

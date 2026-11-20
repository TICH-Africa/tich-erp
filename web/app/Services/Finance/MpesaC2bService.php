<?php

namespace App\Services\Finance;

use App\Models\Applicant;
use App\Models\Invoice;
use App\Models\MpesaC2bTransaction;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentNotification;
use App\Services\ApplicationFeeService;
use App\Services\Sidebar\PortalSidebarBroadcastService;
use App\Support\SchemaProbe;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Paybill (C2B) fees payments. The student dials the paybill themselves, so Safaricom
 * asks us two questions: may this account number take the money (validation), and the
 * money has arrived (confirmation). Only the confirmation may book a payment.
 *
 * An account number is either a student's admission number (their fee bill) or an
 * applicant's application number (the one-off application fee, paid before any student
 * record exists).
 */
class MpesaC2bService
{
    public const ACCEPT = ['ResultCode' => '0', 'ResultDesc' => 'Accepted'];

    /** Safaricom bounces the money back to the payer when this is returned. */
    public const REJECT = ['ResultCode' => 'C2B00012', 'ResultDesc' => 'Rejected'];

    public function __construct(
        protected PaymentService $payments,
        protected MpesaSettingsService $settings,
        protected PortalSidebarBroadcastService $sidebarBroadcast,
        protected ApplicationFeeService $applicationFees,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ResultCode:string,ResultDesc:string}
     */
    public function validate(array $payload): array
    {
        $billRef = $this->billRef($payload);
        $amount = $this->amount($payload);

        $reason = null;
        $student = null;

        if ($billRef === '') {
            $reason = 'No account number was typed with the payment.';
        } elseif ($amount === null || $amount <= 0) {
            $reason = 'The payment amount is not usable.';
        } else {
            $student = $this->findStudent($billRef);

            if ($student) {
                if (! $this->payableInvoice($student)) {
                    $reason = 'That student has no open fee bill.';
                }
            } elseif (! $this->payableApplicant($billRef)) {
                $reason = 'That account number is not on our register or application list.';
            }
        }

        $this->record(MpesaC2bTransaction::KIND_VALIDATION, $payload, [
            'student_id' => $student?->id,
            'status' => $reason === null ? MpesaC2bTransaction::STATUS_ACCEPTED : MpesaC2bTransaction::STATUS_REFUSED,
            'result_code' => $reason === null ? '0' : self::REJECT['ResultCode'],
            'result_desc' => $reason ?? self::ACCEPT['ResultDesc'],
        ]);

        if ($reason !== null) {
            Log::info('M-Pesa paybill validation refused', [
                'bill_ref' => $billRef,
                'amount' => $amount,
                'reason' => $reason,
            ]);

            return self::REJECT;
        }

        return self::ACCEPT;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ResultCode:string,ResultDesc:string}
     */
    public function confirm(array $payload): array
    {
        $billRef = $this->billRef($payload);
        $amount = $this->amount($payload);
        $receipt = $this->receipt($payload);
        $status = trim((string) ($payload['Status'] ?? ''));

        if ($status !== '' && ! in_array(strtolower($status), ['success', 'completed'], true)) {
            $this->record(MpesaC2bTransaction::KIND_CONFIRMATION, $payload, [
                'status' => MpesaC2bTransaction::STATUS_REFUSED,
                'result_desc' => 'Safaricom reported the payment as '.$status.'.',
            ]);

            return self::ACCEPT;
        }

        $student = $billRef === '' ? null : $this->findStudent($billRef);
        $invoice = $student ? $this->payableInvoice($student) : null;
        $applicant = $invoice === null ? $this->payableApplicant($billRef) : null;

        if ($applicant) {
            return $this->bookApplicationFee($payload, $applicant, $amount, $receipt);
        }

        if (! $student || ! $invoice) {
            // Safaricom repeats a confirmation it believes we missed. If that receipt is
            // already on the log - booked for a bill, booked for an application fee, or
            // logged as unmatched - answer again and write nothing a second time.
            if ($this->findClaim($payload) !== null) {
                return self::ACCEPT;
            }

            // The money left the payer but we cannot tie it to an open bill. Keep it on
            // the log so Finance can trace the receipt rather than dropping it silently.
            $this->record(MpesaC2bTransaction::KIND_CONFIRMATION, $payload, [
                'student_id' => $student?->id,
                'status' => MpesaC2bTransaction::STATUS_UNMATCHED,
                'result_desc' => $student ? 'No open fee bill for that admission number.' : 'That account number is not on our register or application list.',
            ]);

            Log::warning('M-Pesa paybill confirmation could not be matched', [
                'bill_ref' => $billRef,
                'receipt' => $receipt,
                'amount' => $amount,
            ]);

            return self::ACCEPT;
        }

        // Claim the receipt before booking: the unique key makes a replayed or racing
        // callback lose, so one M-Pesa transaction can never book two payments.
        $claimed = $this->claim($payload, $student->id);

        if ($claimed === null) {
            return self::ACCEPT;
        }

        if ($claimed->payment_id) {
            $existing = Payment::query()->find($claimed->payment_id);

            if ($existing) {
                return self::ACCEPT;
            }
        }

        if ($claimed->status === MpesaC2bTransaction::STATUS_BOOKED) {
            return self::ACCEPT;
        }

        // The email receipt is left for the manual path: a callback has to answer Safaricom
        // quickly, or it is retried while the first one is still holding a PHP worker.
        $payment = $this->payments->recordPayment($invoice, [
            'amount' => (float) $amount,
            'payment_method' => 'mpesa',
            'payment_reference' => $receipt,
            'transaction_channel_ref' => $this->transactionId($payload),
            'payment_date' => $this->paymentDate($payload),
        ], $this->systemStaffId(), sendConfirmation: false);

        // PaymentService hands back the older row when this receipt is already on the
        // books, so say plainly that nothing new was booked.
        $alreadyBooked = $payment->created_at !== null && $claimed->created_at !== null
            && $payment->created_at->lt($claimed->created_at);

        $claimed->update([
            'payment_id' => $payment->id,
            'status' => $alreadyBooked ? MpesaC2bTransaction::STATUS_DUPLICATE : MpesaC2bTransaction::STATUS_BOOKED,
            'result_code' => '0',
            'result_desc' => $alreadyBooked
                ? 'Receipt was already recorded against payment '.$payment->payment_number.'.'
                : 'Recorded on invoice '.$invoice->invoice_number.' as payment '.$payment->payment_number.'.',
        ]);

        Log::info('M-Pesa paybill payment booked', [
            'receipt' => $receipt,
            'student_id' => $student->id,
            'invoice_id' => $invoice->id,
            'payment_id' => $payment->id,
            'amount' => $amount,
        ]);

        if (! $alreadyBooked) {
            $this->notifyStudent($student, $payment);
        }

        return self::ACCEPT;
    }

    /**
     * Finance puts a receipt we could not match onto the student they name. The amount and
     * the receipt still come from the Safaricom payload, never from the form, and the row is
     * locked while it is claimed so two officers cannot post the same receipt twice.
     */
    public function reconcile(MpesaC2bTransaction $transaction, Student $student, string $reason, int $staffId): Payment
    {
        if ($transaction->kind !== MpesaC2bTransaction::KIND_CONFIRMATION) {
            throw ValidationException::withMessages([
                'student' => 'Only a payment Safaricom has reported can be reconciled, not a permission check.',
            ]);
        }

        if (in_array($transaction->status, [MpesaC2bTransaction::STATUS_BOOKED, MpesaC2bTransaction::STATUS_DUPLICATE], true)) {
            throw ValidationException::withMessages([
                'student' => 'That receipt is already recorded'.($transaction->payment?->payment_number ? ' on payment '.$transaction->payment->payment_number : '').'.',
            ]);
        }

        $invoice = $this->payableInvoice($student);

        if (! $invoice) {
            throw ValidationException::withMessages([
                'student' => $student->registration_number.' has no open fee bill to take this payment.',
            ]);
        }

        $amount = round((float) $transaction->amount, 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'student' => 'Safaricom did not report a usable amount on this receipt, so it cannot be posted.',
            ]);
        }

        $payment = DB::transaction(function () use ($transaction, $student, $invoice, $amount, $reason, $staffId): Payment {
            $locked = MpesaC2bTransaction::query()
                ->whereKey($transaction->id)
                ->whereNotIn('status', [MpesaC2bTransaction::STATUS_BOOKED, MpesaC2bTransaction::STATUS_DUPLICATE])
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                throw ValidationException::withMessages([
                    'student' => 'Another officer has already posted this receipt.',
                ]);
            }

            $payment = $this->payments->recordPayment($invoice, [
                'amount' => $amount,
                'payment_method' => 'mpesa',
                'payment_reference' => $locked->receipt_number ?: $locked->transaction_id,
                'transaction_channel_ref' => $locked->transaction_id ?: $locked->receipt_number,
                'payment_date' => $this->paymentDate(is_array($locked->payload) ? $locked->payload : []),
            ], $staffId);

            if ((int) $payment->student_id !== (int) $student->id) {
                throw ValidationException::withMessages([
                    'student' => 'Safaricom has already recorded receipt '.$payment->payment_reference
                        .' on payment '.$payment->payment_number.' for '
                        .($payment->student?->registration_number ?? 'another student').', so nothing was posted here.',
                ]);
            }

            $locked->update([
                'student_id' => $student->id,
                'payment_id' => $payment->id,
                'status' => MpesaC2bTransaction::STATUS_BOOKED,
                'result_code' => '0',
                'result_desc' => 'Reconciled by Finance onto '.$student->registration_number
                    .' ('.$payment->payment_number.'): '.$reason.'.',
            ]);

            return $payment;
        });

        $this->notifyStudent($student, $payment);

        return $payment;
    }

    public function paybill(): ?string
    {
        return $this->settings->shortcode();
    }

    /**
     * @return array<string, float|int>
     */
    public function paybillSummary(): array
    {
        $empty = ['booked' => 0.0, 'booked_count' => 0, 'unmatched_count' => 0, 'duplicate_count' => 0, 'refused_count' => 0];

        if (! SchemaProbe::hasTable('mpesa_c2b_transactions')) {
            return $empty;
        }

        $confirmations = fn () => MpesaC2bTransaction::query()->where('kind', MpesaC2bTransaction::KIND_CONFIRMATION);

        return [
            'booked' => (float) $confirmations()->where('status', MpesaC2bTransaction::STATUS_BOOKED)->sum('amount'),
            'booked_count' => $confirmations()->where('status', MpesaC2bTransaction::STATUS_BOOKED)->count(),
            'unmatched_count' => $confirmations()->where('status', MpesaC2bTransaction::STATUS_UNMATCHED)->count(),
            'duplicate_count' => $confirmations()->where('status', MpesaC2bTransaction::STATUS_DUPLICATE)->count(),
            'refused_count' => $confirmations()->where('status', MpesaC2bTransaction::STATUS_REFUSED)->count(),
        ];
    }

    /**
     * The student portal has to show the payment without anyone refreshing it or asking
     * Finance to key it in, so the receipt, the amount and the new balance are stated
     * here and the portal caches are dropped.
     */
    private function notifyStudent(Student $student, Payment $payment): void
    {
        $date = $payment->payment_date ? Carbon::parse($payment->payment_date)->format('d M Y') : 'today';
        $receipt = trim((string) ($payment->payment_reference ?? ''));

        if (SchemaProbe::hasTable('student_notifications')) {
            StudentNotification::query()->create([
                'student_id' => $student->id,
                'category' => 'fee',
                'title' => 'M-Pesa payment received',
                'body' => 'Received KES '.number_format((float) $payment->amount, 2)
                    .' on '.$date.' against admission number '.$student->registration_number
                    .($receipt !== '' ? ', M-Pesa receipt '.$receipt : '')
                    .'. Outstanding fees balance is now KES '
                    .number_format($this->outstandingBalance($student), 2).'.',
                'action_url' => '/portal?section=finance',
            ]);
        }

        $this->sidebarBroadcast->broadcastStudent($student);
    }

    /**
     * Same cascade the portal finance summary uses, so the two never disagree.
     */
    private function outstandingBalance(Student $student): float
    {
        $accounts = SchemaProbe::hasTable('student_accounts')
            ? (float) DB::table('student_accounts')->where('student_id', $student->id)->sum('outstanding_balance')
            : 0.0;

        if ($accounts > 0) {
            return $accounts;
        }

        $invoices = (float) Invoice::query()->where('student_id', $student->id)->sum('balance');

        if ($invoices > 0) {
            return $invoices;
        }

        return (float) ($student->fresh()->overall_balance ?? 0);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function claim(array $payload, ?int $studentId): ?MpesaC2bTransaction
    {
        $attributes = $this->payloadColumns(MpesaC2bTransaction::KIND_CONFIRMATION, $payload) + [
            'student_id' => $studentId,
            'status' => 'received',
        ];

        try {
            return DB::transaction(fn () => MpesaC2bTransaction::query()->create($attributes));
        } catch (QueryException $e) {
            if (! $this->isDuplicateKey($e)) {
                throw $e;
            }

            // Someone already claimed this receipt; hand back that row.
            $existing = $this->findClaim($payload);

            if ($existing) {
                Log::info('M-Pesa paybill callback replayed', [
                    'receipt' => $existing->receipt_number,
                    'status' => $existing->status,
                ]);
            }

            return $existing;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function findClaim(array $payload): ?MpesaC2bTransaction
    {
        $receipt = $this->string($payload, ['BillReceipt', 'ReceiptCLanderNumber', 'MpesaReceiptNumber']);
        $localTrxn = $this->string($payload, ['LocalTrxnID']);

        $query = MpesaC2bTransaction::query()->where('kind', MpesaC2bTransaction::KIND_CONFIRMATION);

        if ($receipt !== '') {
            $found = (clone $query)->where('receipt_number', $receipt)->first();

            if ($found) {
                return $found;
            }
        }

        if ($localTrxn !== '') {
            return (clone $query)->where('local_trxn_id', $localTrxn)->first();
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $extra
     */
    private function record(string $kind, array $payload, array $extra): MpesaC2bTransaction
    {
        return MpesaC2bTransaction::query()->create(
            $this->payloadColumns($kind, $payload) + $extra
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function payloadColumns(string $kind, array $payload): array
    {
        return [
            'kind' => $kind,
            'transaction_id' => $this->string($payload, ['TransactionID']) ?: null,
            'receipt_number' => $kind === MpesaC2bTransaction::KIND_CONFIRMATION
                ? ($this->string($payload, ['BillReceipt', 'ReceiptCLanderNumber', 'MpesaReceiptNumber']) ?: null)
                : null,
            'local_trxn_id' => $kind === MpesaC2bTransaction::KIND_CONFIRMATION
                ? ($this->string($payload, ['LocalTrxnID', 'C2BSequence']) ?: null)
                : null,
            'bill_ref_number' => $this->string($payload, ['BillRefNumber']) ?: null,
            'invoice_number' => $this->string($payload, ['InvoiceNumber']) ?: null,
            'msisdn' => $this->string($payload, ['Msisdn']) ?: null,
            'amount' => $this->amount($payload),
            'payload' => $payload,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function billRef(array $payload): string
    {
        return strtoupper(trim($this->string($payload, ['BillRefNumber'])));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function receipt(array $payload): string
    {
        return $this->string($payload, ['BillReceipt', 'ReceiptCLanderNumber', 'MpesaReceiptNumber'])
            ?: $this->string($payload, ['TransactionID', 'LocalTrxnID']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function transactionId(array $payload): string
    {
        return $this->string($payload, ['TransactionID']) ?: $this->receipt($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $keys
     */
    private function string(array $payload, array $keys): string
    {
        foreach ($keys as $key) {
            $value = $payload[$key] ?? null;

            if ($value !== null && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function amount(array $payload): ?float
    {
        $raw = $payload['Amount'] ?? null;

        if ($raw === null || ! is_numeric($raw)) {
            return null;
        }

        return round((float) $raw, 2);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function paymentDate(array $payload): ?string
    {
        $raw = trim((string) ($payload['TransactionDate'] ?? ''));

        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    public function findStudent(string $billRef): ?Student
    {
        return Student::query()
            ->whereRaw('UPPER(TRIM(registration_number)) = ?', [$billRef])
            ->orderBy('id')
            ->first();
    }

    /**
     * An account number that is no admission number can still be an application number,
     * and only an application standing at the payment stage may take the money.
     */
    private function payableApplicant(string $billRef): ?Applicant
    {
        $applicant = $this->applicationFees->findByAccountReference($billRef);

        return $applicant !== null && $applicant->canPayApplicationFee() ? $applicant : null;
    }

    /**
     * The application fee has no invoice before the money arrives, so the receipt settles
     * the applicant record and ApplicationFeeService posts the Finance side behind it.
     */
    private function bookApplicationFee(array $payload, Applicant $applicant, ?float $amount, string $receipt): array
    {
        $claimed = $this->claim($payload, null);

        if ($claimed === null || $claimed->status === MpesaC2bTransaction::STATUS_BOOKED) {
            return self::ACCEPT;
        }

        try {
            $this->applicationFees->markPaidFromMpesa($applicant, $receipt, $amount);
        } catch (\Throwable $e) {
            // The money is with us but the record could not be settled. Keep the receipt on
            // the log so Finance can confirm the fee by hand instead of losing it.
            $claimed->update([
                'status' => MpesaC2bTransaction::STATUS_UNMATCHED,
                'result_desc' => 'Application fee '.$applicant->application_number.' could not be verified: '.$e->getMessage(),
            ]);

            Log::warning('M-Pesa paybill application fee could not be settled', [
                'receipt' => $receipt,
                'applicant_id' => $applicant->id,
                'message' => $e->getMessage(),
            ]);

            return self::ACCEPT;
        }

        $claimed->update([
            'payment_id' => Payment::query()
                ->where('payment_reference', $receipt)
                ->where('status', 'SUCCESS')
                ->value('id'),
            'status' => MpesaC2bTransaction::STATUS_BOOKED,
            'result_code' => '0',
            'result_desc' => 'Application fee '.$applicant->application_number.' verified from receipt '.$receipt.'.',
        ]);

        Log::info('M-Pesa paybill application fee booked', [
            'receipt' => $receipt,
            'applicant_id' => $applicant->id,
            'amount' => $amount,
        ]);

        return self::ACCEPT;
    }

    private function payableInvoice(Student $student): ?Invoice
    {
        return Invoice::query()
            ->where('student_id', $student->id)
            ->whereIn('status', ['issued', 'partial', 'overdue'])
            ->where('balance', '>', 0)
            ->orderBy('issue_date')
            ->orderBy('id')
            ->first();
    }

    private function systemStaffId(): int
    {
        return (int) (Staff::query()->value('id') ?? 1);
    }

    private function isDuplicateKey(QueryException $e): bool
    {
        return (bool) preg_match('/1062|23000|UNIQUE constraint/i', $e->getMessage());
    }
}

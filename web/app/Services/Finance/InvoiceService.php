<?php

namespace App\Services\Finance;

use App\Mail\InvoiceIssuedMail;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\Student;
use App\Support\ModuleMail;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class InvoiceService
{
    public function __construct(
        protected StudentAccountService $accounts,
        protected LedgerService $ledger,
        protected FinanceAuditService $audit,
    ) {}

    /**
     * @param  array{invoice_type: string, description: string, amount: float, semester_id?: int|null}  $data
     */
    public function generateForStudent(Student $student, array $data, ?int $recordedByStaffId = null, bool $dispatch = true): Invoice
    {
        return DB::transaction(function () use ($student, $data, $recordedByStaffId, $dispatch) {
            $student = Student::query()->whereKey($student->id)->lockForUpdate()->firstOrFail();
            $student->loadMissing(['applicant', 'program']);
            $account = $this->accounts->ensureAccount($student);

            $amount = round((float) $data['amount'], 2);
            $invoiceType = (string) $data['invoice_type'];
            $description = (string) $data['description'];

            $recentDuplicate = Invoice::query()
                ->where('student_id', $student->id)
                ->where('invoice_type', $invoiceType)
                ->where('amount', $amount)
                ->where('description', $description)
                ->where('created_at', '>=', now()->subSeconds(90))
                ->orderByDesc('id')
                ->first();

            if ($recentDuplicate) {
                return $recentDuplicate->loadMissing(['student.applicant', 'student.program']);
            }

            $dueDate = isset($data['due_date'])
                ? \Illuminate\Support\Carbon::parse($data['due_date'])->toDateString()
                : now()->addDays(config('finance.invoice_due_days', 30))->toDateString();

            // Read before the new charge exists: any excess payment already on file is
            // spent on this invoice first, so the student is only asked for the rest.
            $creditApplied = round(min($this->accounts->creditAvailable($account), $amount), 2);

            $invoice = Invoice::query()->create([
                'invoice_number' => $this->nextInvoiceNumber($student),
                'student_account_id' => $account->id,
                'student_id' => $student->id,
                'semester_id' => $data['semester_id'] ?? null,
                'invoice_type' => $invoiceType,
                'description' => $description,
                'amount' => $amount,
                'amount_paid' => $creditApplied,
                'balance' => round($amount - $creditApplied, 2),
                'issue_date' => now()->toDateString(),
                'due_date' => $dueDate,
                'status' => match (true) {
                    $creditApplied <= 0 => 'issued',
                    $creditApplied >= $amount => 'paid',
                    default => 'partial',
                },
            ]);

            $this->ledger->postInvoiceRaised($amount, $invoice->invoice_number, $recordedByStaffId, $invoice->invoice_type);
            $this->accounts->recalculate($account);

            if ($dispatch) {
                try {
                    $this->dispatchToChannels($invoice);
                } catch (Throwable $e) {
                    Log::warning('Invoice created but dispatch failed: '.$e->getMessage(), [
                        'invoice_id' => $invoice->id,
                    ]);
                }
            }

            $this->audit->log('finance.invoice.raised', 'invoices', $invoice->id, null, [
                'invoice_number' => $invoice->invoice_number,
                'student_id' => $invoice->student_id,
                'amount' => $amount,
                'credit_applied' => $creditApplied,
                'invoice_type' => $invoice->invoice_type,
            ]);

            return $invoice->fresh(['student.applicant', 'student.program']);
        }, 3);
    }

    /**
     * Overwrite a bill Finance got wrong. The edit replaces the original figures on
     * the same invoice, the money already received stays against it, and anything the
     * student has now overpaid drops onto their account as an excess payment.
     *
     * @param  array{invoice_type?: string, description?: string, amount: float, due_date?: string|null}  $data
     */
    public function amendInvoice(Invoice $invoice, array $data, ?int $recordedByStaffId = null): Invoice
    {
        return DB::transaction(function () use ($invoice, $data, $recordedByStaffId) {
            /** @var Invoice $locked */
            $locked = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $locked->loadMissing(['studentAccount', 'student']);

            $account = $locked->studentAccount ?? $this->accounts->ensureAccount($locked->student);

            $oldAmount = round((float) $locked->amount, 2);
            $newAmount = round((float) $data['amount'], 2);
            // Money this bill already took never disappears: an edit that lowers the
            // charge leaves the difference on the account, to be spent on the next bill.
            $paid = round(min((float) $locked->amount_paid, $newAmount), 2);
            $balance = round(max($newAmount - $paid, 0), 2);

            $locked->update([
                'invoice_type' => (string) ($data['invoice_type'] ?? $locked->invoice_type),
                'description' => (string) ($data['description'] ?? $locked->description),
                'amount' => $newAmount,
                'amount_paid' => $paid,
                'balance' => $balance,
                'due_date' => isset($data['due_date']) && $data['due_date']
                    ? \Illuminate\Support\Carbon::parse($data['due_date'])->toDateString()
                    : $locked->due_date,
                'status' => match (true) {
                    $balance <= 0 => 'paid',
                    $locked->due_date !== null && \Illuminate\Support\Carbon::parse($locked->due_date)->isPast() => 'overdue',
                    $paid > 0 => 'partial',
                    default => 'issued',
                },
            ]);

            $delta = round($newAmount - $oldAmount, 2);
            if (abs($delta) >= 0.01) {
                $this->ledger->postInvoiceAmendment($delta, $locked->invoice_number, $recordedByStaffId, $locked->invoice_type);
            }

            $this->accounts->recalculate($account);

            $this->audit->log('finance.invoice.amended', 'invoices', $locked->id, [
                'amount' => $oldAmount,
                'status' => $invoice->status,
            ], [
                'amount' => $newAmount,
                'status' => $locked->status,
                'balance' => $balance,
                'invoice_type' => $locked->invoice_type,
                'description' => $locked->description,
            ]);

            return $locked->fresh(['student.applicant', 'student.program', 'payments']);
        }, 3);
    }

    /**
     * Bill every active student, one page at a time so a single click cannot outlive
     * the web request on a large cohort. The caller returns the cursor it must post
     * back to carry on where this page stopped.
     *
     * @param  Closure(Student):Invoice  $bill
     * @return array{raised: int, existing: int, after_id: int, remaining: int}
     */
    public function billActiveStudents(int $afterStudentId, int $limit, Closure $bill): array
    {
        $students = $this->activeStudentsQuery($afterStudentId)->limit($limit)->get();

        // Anything raised by this run takes a new invoice id, so the first free id
        // tells a fresh bill from the duplicate the guard hands back.
        $firstNewId = (int) Invoice::query()->max('id') + 1;
        $raised = 0;
        $existing = 0;
        $lastId = $afterStudentId;

        foreach ($students as $student) {
            $invoice = $bill($student);
            $lastId = (int) $student->id;
            if ((int) $invoice->id >= $firstNewId) {
                $raised++;
            } else {
                $existing++;
            }
        }

        return [
            'raised' => $raised,
            'existing' => $existing,
            'after_id' => $lastId,
            'remaining' => $this->activeStudentsQuery($lastId)->count(),
        ];
    }

    public function activeStudentCount(): int
    {
        return $this->activeStudentsQuery(0)->count();
    }

    private function activeStudentsQuery(int $afterStudentId): \Illuminate\Database\Eloquent\Builder
    {
        return Student::query()
            ->where('is_active', 1)
            ->where('id', '>', $afterStudentId)
            ->orderBy('id');
    }

    /** Semester charges invoice from an approved programme fee structure. */
    public function generateSemesterInvoice(Student $student, FeeStructure $feeStructure, ?int $recordedByStaffId = null, bool $includeOptional = false): Invoice
    {
        $feeStructure->loadMissing(['program', 'academicYear']);

        $amount = (float) $feeStructure->total_semester_fee;
        if ($includeOptional) {
            $amount += $feeStructure->optionalSemesterTotal();
        }

        $description = sprintf(
            "Semester charges - %s (%s)\n%s",
            $feeStructure->program?->program_name ?? 'Programme',
            $feeStructure->academicYear?->year_label ?? 'Academic year',
            implode('; ', $feeStructure->semesterChargeLines($includeOptional))
        );

        return $this->generateForStudent($student, [
            'invoice_type' => 'tuition',
            'description' => $description,
            'amount' => $amount,
        ], $recordedByStaffId);
    }

    public function generateApplicationInvoice(Student $student, FeeStructure $feeStructure, ?int $recordedByStaffId = null): Invoice
    {
        $feeStructure->loadMissing(['program', 'academicYear']);

        return $this->generateForStudent($student, [
            'invoice_type' => 'application',
            'description' => sprintf(
                'Application fee - %s (paid once after approval)',
                $feeStructure->program?->program_name ?? 'Programme'
            ),
            'amount' => (float) $feeStructure->application_fee,
        ], $recordedByStaffId);
    }

    public function generateQaAnnualInvoice(Student $student, FeeStructure $feeStructure, ?int $recordedByStaffId = null): Invoice
    {
        return $this->generateForStudent($student, [
            'invoice_type' => 'qa_annual',
            'description' => 'Quality assurance fee (annual)',
            'amount' => (float) $feeStructure->qa_annual_fee,
        ], $recordedByStaffId);
    }

    public function generateIndexingInvoice(Student $student, FeeStructure $feeStructure, ?int $recordedByStaffId = null): Invoice
    {
        abort_unless($feeStructure->requires_indexing_nck && $feeStructure->indexing_nck_fee, 422, 'Indexing (NCK) is not configured for this programme.');

        return $this->generateForStudent($student, [
            'invoice_type' => 'indexing_nck',
            'description' => 'Indexing (NCK) - paid once throughout the programme',
            'amount' => (float) $feeStructure->indexing_nck_fee,
        ], $recordedByStaffId);
    }

    public function generateGraduationInvoice(Student $student, FeeStructure $feeStructure, ?int $recordedByStaffId = null): Invoice
    {
        return $this->generateForStudent($student, [
            'invoice_type' => 'graduation',
            'description' => 'Graduation fees (post learning)',
            'amount' => (float) $feeStructure->graduation_fee,
        ], $recordedByStaffId);
    }

    /** @deprecated Use generateSemesterInvoice() */
    public function generateFromFeeStructure(Student $student, FeeStructure $feeStructure, ?int $recordedByStaffId = null): Invoice
    {
        return $this->generateSemesterInvoice($student, $feeStructure, $recordedByStaffId);
    }

    public function dispatchToChannels(Invoice $invoice): void
    {
        $invoice->update([
            'is_sent_to_portal' => 1,
            'sent_at' => now(),
        ]);

        $invoice->loadMissing(['student.applicant', 'student.user']);

        $email = $invoice->student?->applicant?->email
            ?? $invoice->student?->user?->email;

        if (! $email) {
            return;
        }

        try {
            ModuleMail::send(ModuleMail::FINANCE, $email, new InvoiceIssuedMail($invoice));
        } catch (Throwable $e) {
            Log::error('Failed to send invoice email', [
                'invoice_id' => $invoice->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function nextInvoiceNumber(Student $student): string
    {
        $registration = preg_replace('/\s+/', '', (string) ($student->registration_number ?? 'STU-'.$student->id));
        $prefix = $registration.' - ';

        $latest = Invoice::query()
            ->where('student_id', $student->id)
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('invoice_number');

        $sequence = 1;
        if ($latest && preg_match('/-\s*(\d+)$/', $latest, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }
}

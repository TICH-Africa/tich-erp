<?php

namespace App\Services\Finance;

use App\Mail\InvoiceIssuedMail;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\Student;
use App\Support\ModuleMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class InvoiceService
{
    public function __construct(
        protected StudentAccountService $accounts,
        protected LedgerService $ledger,
    ) {}

    /**
     * @param  array{invoice_type: string, description: string, amount: float, semester_id?: int|null}  $data
     */
    public function generateForStudent(Student $student, array $data, ?int $recordedByStaffId = null, bool $dispatch = true): Invoice
    {
        return DB::transaction(function () use ($student, $data, $recordedByStaffId, $dispatch) {
            $student->loadMissing(['applicant', 'program']);
            $account = $this->accounts->ensureAccount($student);

            $amount = round((float) $data['amount'], 2);
            $invoice = Invoice::query()->create([
                'invoice_number' => $this->nextInvoiceNumber($student),
                'student_account_id' => $account->id,
                'student_id' => $student->id,
                'semester_id' => $data['semester_id'] ?? null,
                'invoice_type' => $data['invoice_type'],
                'description' => $data['description'],
                'amount' => $amount,
                'amount_paid' => 0,
                'balance' => $amount,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(config('finance.invoice_due_days', 30))->toDateString(),
                'status' => 'issued',
            ]);

            $this->ledger->postInvoiceRaised($amount, $invoice->invoice_number, $recordedByStaffId);
            $this->accounts->recalculate($account);

            if ($dispatch) {
                $this->dispatchToChannels($invoice);
            }

            return $invoice->fresh(['student.applicant', 'student.program']);
        });
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
            "Semester charges — %s (%s)\n%s",
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
                'Application fee — %s (paid once after approval)',
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
            'description' => 'Indexing (NCK) — paid once throughout the programme',
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

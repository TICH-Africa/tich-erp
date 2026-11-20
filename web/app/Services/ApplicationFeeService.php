<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\Staff;
use App\Models\Student;
use App\Services\Finance\InvoiceService;
use App\Services\Finance\MpesaSettingsService;
use App\Services\Finance\PaymentService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

class ApplicationFeeService
{
    public function __construct(
        protected MpesaSettingsService $mpesaSettings,
        protected SiteSettingsService $siteSettings,
        protected AuditService $auditService,
        protected ApplicationMailService $mailService,
    ) {}

    public function amountDue(Applicant $applicant): float
    {
        $fromStructure = null;

        if ($applicant->program_id && Schema::hasTable('fee_structures')) {
            $fromStructure = FeeStructure::query()
                ->where('program_id', $applicant->program_id)
                ->where('is_active', 1)
                ->orderByDesc('is_approved')
                ->orderByDesc('effective_from')
                ->value('application_fee');
        }

        return round((float) ($fromStructure ?? config('finance.fee_defaults.application_fee', 1000)), 2);
    }

    public function accountReference(Applicant $applicant): string
    {
        $digits = preg_replace('/[^A-Z0-9]/i', '', (string) $applicant->application_number) ?: 'APPFEE';

        return strtoupper(substr($digits, 0, 12));
    }

    /**
     * The applicant pays on the same paybill the students use, so the account number they
     * type is their own application reference. Safaricom sends that reference back on the
     * confirmation and MpesaC2bService settles the fee with it.
     */
    public function findByAccountReference(string $accountReference): ?Applicant
    {
        $reference = strtoupper(preg_replace('/[^A-Z0-9]/i', '', trim($accountReference)) ?? '');

        if ($reference === '') {
            return null;
        }

        return Applicant::query()
            ->whereRaw("LEFT(REPLACE(UPPER(application_number), '-', ''), 12) = ?", [$reference])
            ->orderByDesc('id')
            ->first();
    }

    public function paybill(): string
    {
        return trim((string) ($this->mpesaSettings->shortcode() ?? ''));
    }

    public function paybillName(): string
    {
        return trim((string) ($this->siteSettings->documentBranding()['name'] ?? ''));
    }

    public function payUrl(Applicant $applicant): string
    {
        return route('apply.pay', [
            'application_number' => $applicant->application_number,
            'email' => $applicant->email,
        ]);
    }

    /**
     * @return array{amount: float, account_reference: string, paybill: string, paybill_name: string, pay_url: string}
     */
    public function paymentInstructions(Applicant $applicant): array
    {
        return [
            'amount' => $this->amountDue($applicant),
            'account_reference' => $this->accountReference($applicant),
            'paybill' => $this->paybill(),
            'paybill_name' => $this->paybillName(),
            'pay_url' => $this->payUrl($applicant),
        ];
    }

    public function mpesaEnabled(): bool
    {
        return $this->mpesaSettings->isEnabled();
    }

    public function simulateLocalPayment(Applicant $applicant): Applicant
    {
        if (! config('finance.mpesa.allow_local_simulate')) {
            throw ValidationException::withMessages([
                'application' => 'Local payment simulation is disabled. Enable M-Pesa sandbox credentials to test paybill payments.',
            ]);
        }

        $this->assertPayable($applicant);

        return $this->markPaid(
            $applicant,
            'SIM-APP-'.strtoupper(substr(sha1((string) microtime(true)), 0, 8)),
            'simulated'
        );
    }

    /**
     * Undo a recorded application fee payment so paybill testing can be repeated.
     */
    public function revertApplicationFeePayment(Applicant $applicant, ?int $actorId = null): Applicant
    {
        if (! $applicant->application_fee_paid && $applicant->status === 'fee_pending') {
            return $applicant;
        }

        if ($applicant->status === 'rejected') {
            throw ValidationException::withMessages([
                'application' => 'Cannot revert payment on a rejected application.',
            ]);
        }

        $previousStatus = $applicant->status;
        $previousRef = $applicant->application_fee_payment_ref;

        $payload = [
            'status' => 'fee_pending',
            'application_fee_paid' => false,
            'application_fee_paid_at' => null,
            'reviewed_at' => $applicant->reviewed_at,
        ];

        if (Schema::hasColumn('applicants', 'application_fee_payment_ref')) {
            $payload['application_fee_payment_ref'] = null;
        }

        $applicant->update($payload);

        $this->auditService->log(
            'admissions.application.fee_reverted',
            'applicants',
            $applicant->id,
            [
                'status' => $previousStatus,
                'payment_reference' => $previousRef,
            ],
            [
                'status' => 'fee_pending',
                'application_fee_paid' => false,
            ],
            'Application fee payment reverted for retesting',
            'success',
            $actorId
        );

        return $applicant->fresh(['program.department', 'handlingDepartment']);
    }

    public function markPaidFromMpesa(Applicant $applicant, string $receipt, ?float $amount = null): Applicant
    {
        return $this->markPaid($applicant, $receipt !== '' ? $receipt : 'MPESA-AUTO', 'mpesa', $amount);
    }

    public function syncFromInvoice(Invoice $invoice, ?string $reference = null): void
    {
        if ($invoice->invoice_type !== 'application') {
            return;
        }

        $invoice->loadMissing('student');
        $applicantId = $invoice->student?->application_id;

        if (! $applicantId) {
            return;
        }

        $applicant = Applicant::query()->find($applicantId);

        if (! $applicant) {
            return;
        }

        $this->markPaid(
            $applicant,
            $reference ?: $invoice->invoice_number,
            'invoice'
        );
    }

    public function markPaid(
        Applicant $applicant,
        string $reference,
        string $source = 'manual',
        ?float $amount = null,
        ?string $notes = null,
    ): Applicant {
        if ($applicant->isFinalized()) {
            if (! $applicant->application_fee_paid) {
                $this->writePaidFlags($applicant, $reference, $notes);
            }

            if ($source !== 'invoice') {
                try {
                    $this->ensureFinancePaymentRecord(
                        $applicant->fresh(),
                        $reference,
                        $source === 'simulated' ? 'cash' : ($source === 'mpesa' ? 'mpesa' : 'manual'),
                        $amount ?? $this->amountDue($applicant),
                    );
                } catch (Throwable $e) {
                    Log::warning('Finalized applicant fee finance posting failed', [
                        'applicant_id' => $applicant->id,
                        'reference' => $reference,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            return $applicant->fresh(['program.department', 'handlingDepartment']);
        }

        if (! in_array($source, ['mpesa', 'invoice', 'simulated'], true)
            && $applicant->academic_review_status !== 'approved'
            && $applicant->status !== 'fee_pending') {
            throw ValidationException::withMessages([
                'application' => 'Application fee can only be confirmed after academic approval.',
            ]);
        }

        $alreadyPaid = $applicant->application_fee_paid && $applicant->status === 'paid';

        $this->writePaidFlags($applicant, $reference, $notes);

        $this->auditService->log(
            'admissions.application.fee_verified',
            'applicants',
            $applicant->id,
            null,
            [
                'application_number' => $applicant->application_number,
                'status' => 'paid',
                'payment_reference' => $reference,
                'source' => $source,
                'amount' => $amount ?? $this->amountDue($applicant),
            ],
            'Application fee payment verified via '.$source,
            'success',
            null
        );

        $applicant = $applicant->fresh(['program.department', 'handlingDepartment']);

        if (! $alreadyPaid) {
            app(AdmissionsReviewService::class)->finalizeAfterPayment($applicant);
            $applicant = $applicant->fresh(['program.department', 'handlingDepartment']);
        }

        // Keep applicant STK payments visible in Finance (invoice + payment + receipt).
        // Skip when Finance already recorded the payment against an application invoice.
        if ($source !== 'invoice') {
            try {
                $this->ensureFinancePaymentRecord(
                    $applicant,
                    $reference,
                    $source === 'simulated' ? 'cash' : ($source === 'mpesa' ? 'mpesa' : 'manual'),
                    $amount ?? $this->amountDue($applicant),
                );
            } catch (Throwable $e) {
                Log::warning('Application fee marked paid but finance payment posting failed', [
                    'applicant_id' => $applicant->id,
                    'reference' => $reference,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $applicant->fresh(['program.department', 'handlingDepartment']);
    }

    /**
     * Create (or reuse) an application invoice and a matching Payment so Finance can see the fee.
     */
    public function ensureFinancePaymentRecord(
        Applicant $applicant,
        string $reference,
        string $paymentMethod = 'mpesa',
        ?float $amount = null,
        ?int $recordedByStaffId = null,
    ): ?\App\Models\Payment {
        $amount = round($amount ?? $this->amountDue($applicant), 2);
        if ($amount <= 0) {
            return null;
        }

        $student = Student::query()->where('application_id', $applicant->id)->first();

        if (! $student) {
            $student = app(StudentEnrollmentService::class)
                ->enrollFromAdmittedApplicant($applicant, $recordedByStaffId);
        }

        $existing = \App\Models\Payment::query()
            ->where('payment_reference', $reference)
            ->where('status', 'SUCCESS')
            ->first();

        if ($existing) {
            return $existing;
        }

        $invoiceService = app(InvoiceService::class);
        $paymentService = app(PaymentService::class);
        $staffId = $recordedByStaffId ?? (int) (Staff::query()->value('id') ?? 1);

        $invoice = Invoice::query()
            ->where('student_id', $student->id)
            ->where('invoice_type', 'application')
            ->whereIn('status', ['issued', 'partial', 'overdue', 'paid'])
            ->orderByDesc('id')
            ->first();

        if (! $invoice) {
            $feeStructure = null;
            if ($applicant->program_id && Schema::hasTable('fee_structures')) {
                $feeStructure = FeeStructure::query()
                    ->where('program_id', $applicant->program_id)
                    ->where('is_active', 1)
                    ->orderByDesc('is_approved')
                    ->orderByDesc('effective_from')
                    ->first();
            }

            if ($feeStructure && (float) $feeStructure->application_fee > 0) {
                $invoice = $invoiceService->generateApplicationInvoice($student, $feeStructure, $staffId);
            } else {
                $invoice = $invoiceService->generateForStudent($student, [
                    'invoice_type' => 'application',
                    'description' => 'Application fee - '.$applicant->application_number,
                    'amount' => $amount,
                ], $staffId, false);
            }
        }

        if ((float) $invoice->balance <= 0 && $invoice->status === 'paid') {
            return \App\Models\Payment::query()
                ->where('invoice_id', $invoice->id)
                ->where('status', 'SUCCESS')
                ->orderByDesc('id')
                ->first();
        }

        return $paymentService->recordPayment($invoice, [
            'amount' => min($amount, (float) $invoice->balance ?: $amount),
            'payment_method' => $paymentMethod,
            'payment_reference' => $reference,
            'payment_date' => optional($applicant->application_fee_paid_at)?->toDateString() ?? now()->toDateString(),
        ], $staffId, false);
    }

    private function writePaidFlags(Applicant $applicant, string $reference, ?string $notes): void
    {
        $payload = [
            'status' => $applicant->status === 'admitted' ? 'admitted' : 'paid',
            'application_fee_paid' => true,
            'application_fee_paid_at' => $applicant->application_fee_paid_at ?? now(),
            'reviewed_at' => now(),
        ];

        if (Schema::hasColumn('applicants', 'application_fee_payment_ref')) {
            $payload['application_fee_payment_ref'] = $reference;
        }

        if ($notes !== null) {
            $payload['review_notes'] = $notes;
        }

        $applicant->update($payload);
    }

    private function assertPayable(Applicant $applicant): void
    {
        if ($applicant->application_fee_paid || $applicant->status === 'paid') {
            throw ValidationException::withMessages([
                'application' => 'Application fee has already been verified.',
            ]);
        }

        if ($applicant->isFinalized()) {
            throw ValidationException::withMessages([
                'application' => 'This application has already been finalized.',
            ]);
        }

        if ($applicant->academic_review_status !== 'approved' && $applicant->status !== 'fee_pending') {
            throw ValidationException::withMessages([
                'application' => 'Pay the application fee only after academic approval.',
            ]);
        }
    }
}

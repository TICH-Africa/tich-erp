<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\MpesaStkRequest;
use App\Services\Finance\MpesaDarajaService;
use App\Services\Finance\MpesaSettingsService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

class ApplicationFeeService
{
    public function __construct(
        protected MpesaSettingsService $mpesaSettings,
        protected MpesaDarajaService $mpesaDaraja,
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

    public function payUrl(Applicant $applicant): string
    {
        return route('apply.pay', [
            'application_number' => $applicant->application_number,
            'email' => $applicant->email,
        ]);
    }

    /**
     * @return array{amount: float, account_reference: string, pay_url: string}
     */
    public function paymentInstructions(Applicant $applicant): array
    {
        return [
            'amount' => $this->amountDue($applicant),
            'account_reference' => $this->accountReference($applicant),
            'pay_url' => $this->payUrl($applicant),
        ];
    }

    public function mpesaEnabled(): bool
    {
        return $this->mpesaSettings->isEnabled();
    }

    public function initiateStk(Applicant $applicant, string $phoneNumber): MpesaStkRequest
    {
        $this->assertPayable($applicant);

        if (! $this->mpesaEnabled()) {
            throw ValidationException::withMessages([
                'phone_number' => 'M-Pesa is not enabled. If you have already paid, Finance can confirm the receipt manually.',
            ]);
        }

        $amount = $this->amountDue($applicant);
        abort_if($amount <= 0, 422, 'No application fee is configured for this programme.');

        $normalizedPhone = $this->mpesaDaraja->normalizePhone($phoneNumber);

        $recentPending = MpesaStkRequest::query()
            ->where('applicant_id', $applicant->id)
            ->where('status', MpesaStkRequest::STATUS_PENDING)
            ->where('created_at', '>=', now()->subMinutes(3))
            ->exists();

        abort_if($recentPending, 422, 'An M-Pesa prompt is already pending. Check your phone or wait a moment.');

        $accountReference = $this->accountReference($applicant);

        $stkRequest = MpesaStkRequest::query()->create([
            'invoice_id' => null,
            'student_id' => null,
            'applicant_id' => $applicant->id,
            'amount' => $amount,
            'phone' => $normalizedPhone,
            'account_reference' => $accountReference,
            'status' => MpesaStkRequest::STATUS_PENDING,
        ]);

        try {
            $response = $this->mpesaDaraja->stkPush(
                $amount,
                $normalizedPhone,
                $accountReference,
                'TICH application fee '.$applicant->application_number,
            );

            $stkRequest->update([
                'merchant_request_id' => $response['merchant_request_id'],
                'checkout_request_id' => $response['checkout_request_id'],
            ]);
        } catch (Throwable $e) {
            $stkRequest->update([
                'status' => MpesaStkRequest::STATUS_FAILED,
                'result_desc' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }

        return $stkRequest->fresh();
    }

    public function simulateLocalPayment(Applicant $applicant): Applicant
    {
        $this->assertPayable($applicant);

        return $this->markPaid(
            $applicant,
            'SIM-APP-'.strtoupper(substr(sha1((string) microtime(true)), 0, 8)),
            'simulated'
        );
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

            return $applicant->fresh(['program.department', 'handlingDepartment']);
        }

        if (! in_array($source, ['mpesa', 'invoice', 'simulated'], true)
            && $applicant->academic_review_status !== 'shortlisted'
            && $applicant->status !== 'fee_pending') {
            throw ValidationException::withMessages([
                'application' => 'Application fee can only be confirmed after academic shortlisting.',
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
            $this->mailService->sendStatusUpdate($applicant);
        }

        return $applicant;
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

        if ($applicant->academic_review_status !== 'shortlisted' && $applicant->status !== 'fee_pending') {
            throw ValidationException::withMessages([
                'application' => 'Pay the application fee only after academic validation.',
            ]);
        }
    }
}

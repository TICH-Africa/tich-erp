<?php

namespace App\Mail;

use App\Mail\Concerns\UsesModuleEnvelope;
use App\Models\Applicant;
use App\Support\ModuleMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels, UsesModuleEnvelope;

    /**
     * @param  array{path: string, filename: string}|null  $admissionLetter
     * @param  array{amount: float, reference: string, method: string, paid_at: ?string, payment_number: ?string}|null  $paymentConfirmation
     */
    public function __construct(
        public Applicant $applicant,
        public string $programName,
        public string $statusLabel,
        public string $reviewLabel,
        public string $statusUrl,
        public ?string $rejectionReason = null,
        public ?string $reviewNotes = null,
        public ?string $portalActivationUrl = null,
        public ?array $admissionLetter = null,
        public ?array $paymentConfirmation = null,
    ) {}

    protected function mailModule(): string
    {
        return ModuleMail::ACADEMICS;
    }

    public function envelope(): Envelope
    {
        $subject = $this->paymentConfirmation !== null && $this->portalActivationUrl !== null
            ? 'TICH - Payment received & student portal access ('.$this->applicant->application_number.')'
            : ($this->paymentConfirmation !== null
                ? 'TICH - Application fee payment received ('.$this->applicant->application_number.')'
                : 'TICH - Application update ('.$this->applicant->application_number.')');

        return $this->moduleEnvelope($subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.application-status-updated',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if (! $this->admissionLetter) {
            return [];
        }

        return [
            Attachment::fromStorageDisk('public', $this->admissionLetter['path'])
                ->as($this->admissionLetter['filename']),
        ];
    }
}

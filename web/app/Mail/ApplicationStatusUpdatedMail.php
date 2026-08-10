<?php

namespace App\Mail;

use App\Mail\Concerns\UsesModuleEnvelope;
use App\Models\Applicant;
use App\Support\ModuleMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels, UsesModuleEnvelope;

    public function __construct(
        public Applicant $applicant,
        public string $programName,
        public string $statusLabel,
        public string $reviewLabel,
        public string $statusUrl,
        public ?string $rejectionReason = null,
        public ?string $reviewNotes = null,
        public ?string $portalActivationUrl = null,
    ) {}

    protected function mailModule(): string
    {
        return ModuleMail::ACADEMICS;
    }

    public function envelope(): Envelope
    {
        return $this->moduleEnvelope('TICH - Application update ('.$this->applicant->application_number.')');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.application-status-updated',
        );
    }
}

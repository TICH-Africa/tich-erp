<?php

namespace App\Mail;

use App\Models\Applicant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Applicant $applicant,
        public string $programName,
        public string $statusLabel,
        public string $reviewLabel,
        public string $statusUrl,
        public ?string $rejectionReason = null,
        public ?string $reviewNotes = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'TICH — Application update ('.$this->applicant->application_number.')',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.application-status-updated',
        );
    }
}

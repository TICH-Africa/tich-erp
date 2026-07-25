<?php

namespace App\Mail;

use App\Models\Applicant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationShortlistedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Applicant $applicant,
        public string $programName,
        public string $statusUrl,
        public string $admissionFeeNotice,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'TICH - Application shortlisted ('.$this->applicant->application_number.')',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.application-shortlisted',
        );
    }
}

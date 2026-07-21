<?php

namespace App\Mail;

use App\Models\Applicant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationStaffReviewMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Applicant $applicant,
        public User $reviewer,
        public string $programName,
        public string $departmentName,
        public string $reviewUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'TICH — New application for review ('.$this->applicant->application_number.')',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.application-staff-review',
        );
    }
}

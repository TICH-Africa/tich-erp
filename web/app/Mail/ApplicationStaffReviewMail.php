<?php

namespace App\Mail;

use App\Mail\Concerns\UsesModuleEnvelope;
use App\Models\Applicant;
use App\Models\User;
use App\Support\ModuleMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationStaffReviewMail extends Mailable
{
    use Queueable, SerializesModels, UsesModuleEnvelope;

    public function __construct(
        public Applicant $applicant,
        public User $reviewer,
        public string $programName,
        public string $departmentName,
        public string $reviewUrl,
    ) {}

    protected function mailModule(): string
    {
        return ModuleMail::NOTIFICATION;
    }

    public function envelope(): Envelope
    {
        return $this->moduleEnvelope('TICH - New application for review ('.$this->applicant->application_number.')');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.application-staff-review',
        );
    }
}

<?php

namespace App\Mail;

use App\Models\Staff;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OnboardingRejectedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Staff $staff,
        public string $rejectionReason
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Action Required: Onboarding Information Needs Update',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.onboarding-rejected',
            with: [
                'staff' => $this->staff,
                'rejectionReason' => $this->rejectionReason,
            ],
        );
    }
}

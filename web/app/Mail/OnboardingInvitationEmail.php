<?php

namespace App\Mail;

use App\Models\Staff;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OnboardingInvitationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Staff $staff
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Complete your onboarding - TICH',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.onboarding-invitation',
            with: [
                'staff' => $this->staff,
            ],
        );
    }
}

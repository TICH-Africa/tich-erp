<?php

namespace App\Mail;

use App\Mail\Concerns\UsesModuleEnvelope;
use App\Models\Staff;
use App\Support\ModuleMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OnboardingInvitationEmail extends Mailable
{
    use Queueable, SerializesModels, UsesModuleEnvelope;

    public function __construct(
        public Staff $staff
    ) {}

    protected function mailModule(): string
    {
        return ModuleMail::HR;
    }

    public function envelope(): Envelope
    {
        return $this->moduleEnvelope('Complete your onboarding - TICH');
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

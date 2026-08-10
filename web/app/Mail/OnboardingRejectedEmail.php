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

class OnboardingRejectedEmail extends Mailable
{
    use Queueable, SerializesModels, UsesModuleEnvelope;

    public function __construct(
        public Staff $staff,
        public string $rejectionReason
    ) {}

    protected function mailModule(): string
    {
        return ModuleMail::HR;
    }

    public function envelope(): Envelope
    {
        return $this->moduleEnvelope('Action Required: Onboarding Information Needs Update');
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

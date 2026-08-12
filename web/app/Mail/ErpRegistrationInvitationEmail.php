<?php

namespace App\Mail;

use App\Mail\Concerns\UsesModuleEnvelope;
use App\Models\ErpRegistrationInvitation;
use App\Models\Staff;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ErpRegistrationInvitationEmail extends Mailable
{
    use Queueable, SerializesModels, UsesModuleEnvelope;

    public function __construct(
        public ErpRegistrationInvitation $invitation,
        public ?Staff $staff = null,
    ) {}

    protected function mailModule(): string
    {
        return $this->invitation->sent_via_module;
    }

    public function envelope(): Envelope
    {
        return $this->moduleEnvelope('You are invited to register on TICH ERP');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.erp-registration-invitation',
            with: [
                'invitation' => $this->invitation,
                'staff' => $this->staff,
                'registerUrl' => $this->invitation->registerUrl(),
                'departmentLabel' => match ($this->invitation->sent_via_module) {
                    'ict' => 'Information & Communication Technology',
                    default => 'Human Resources',
                },
            ],
        );
    }
}

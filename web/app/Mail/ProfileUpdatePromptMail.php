<?php

namespace App\Mail;

use App\Mail\Concerns\UsesModuleEnvelope;
use App\Models\Staff;
use App\Models\StaffProfileUpdatePrompt;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProfileUpdatePromptMail extends Mailable
{
    use Queueable, SerializesModels, UsesModuleEnvelope;

    public function __construct(
        public StaffProfileUpdatePrompt $prompt,
        public Staff $staff,
    ) {}

    protected function mailModule(): string
    {
        return $this->prompt->requested_via_module === 'ict' ? 'ict' : 'hr';
    }

    public function envelope(): Envelope
    {
        return $this->moduleEnvelope('Please update your employee profile on TICH ERP');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.profile-update-prompt',
            with: [
                'prompt' => $this->prompt,
                'staff' => $this->staff,
                'portalUrl' => $this->prompt->portalUrl(),
                'fieldLabels' => $this->prompt->fieldLabels(),
                'departmentLabel' => $this->prompt->requested_via_module === 'ict'
                    ? 'Information & Communication Technology'
                    : 'Human Resources',
            ],
        );
    }
}

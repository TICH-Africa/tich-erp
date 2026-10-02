<?php

namespace App\Mail;

use App\Mail\Concerns\UsesModuleEnvelope;
use App\Models\ErpRegistrationInvitation;
use App\Models\Staff;
use App\Models\User;
use App\Support\ModuleMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ErpRegistrationCompletedMail extends Mailable
{
    use Queueable, SerializesModels, UsesModuleEnvelope;

    /**
     * @param  'registrant'|'inviter'  $audience
     */
    public function __construct(
        public string $audience,
        public User $registrant,
        public ErpRegistrationInvitation $invitation,
        public ?Staff $staff = null,
        public ?User $inviter = null,
    ) {}

    protected function mailModule(): string
    {
        return ModuleMail::NOTIFICATION;
    }

    public function envelope(): Envelope
    {
        $subject = $this->audience === 'inviter'
            ? 'Your invitee completed TICH ERP registration'
            : 'Welcome - your TICH ERP account is ready';

        return $this->moduleEnvelope($subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.erp-registration-completed',
            with: [
                'audience' => $this->audience,
                'registrant' => $this->registrant,
                'invitation' => $this->invitation,
                'staff' => $this->staff,
                'inviter' => $this->inviter,
                'registrantName' => $this->staff?->fullName() ?: $this->registrant->email,
                'loginUrl' => \App\Support\MailPublicUrl::to('login'),
            ],
        );
    }
}

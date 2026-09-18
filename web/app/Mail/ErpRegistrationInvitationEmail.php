<?php

namespace App\Mail;

use App\Mail\Concerns\UsesModuleEnvelope;
use App\Models\ErpRegistrationInvitation;
use App\Models\Staff;
use App\Support\ModuleMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email as SymfonyEmail;

class ErpRegistrationInvitationEmail extends Mailable
{
    use Queueable, SerializesModels, UsesModuleEnvelope;

    /**
     * @param  string  $deliveryModule  SMTP mailbox used to send (notification is the proven-delivery path).
     */
    public function __construct(
        public ErpRegistrationInvitation $invitation,
        public ?Staff $staff = null,
        public string $deliveryModule = ModuleMail::NOTIFICATION,
    ) {}

    protected function mailModule(): string
    {
        return $this->deliveryModule;
    }

    public function envelope(): Envelope
    {
        // Same path as the working notification mailbox test: auth + From + Reply-To
        // all use notification@. HR/ICT identity is the display name + body label only
        // (hr@ / ict@ do not reach external inboxes reliably on this host).
        $delivery = ModuleMail::from($this->deliveryModule);
        $brandName = match ($this->invitation->sent_via_module) {
            'ict' => ModuleMail::from(ModuleMail::ICT)['name'],
            'hr' => ModuleMail::from(ModuleMail::HR)['name'],
            default => $delivery['name'],
        };
        $domain = substr(strrchr($delivery['address'], '@') ?: '@tich.africa', 1) ?: 'tich.africa';

        return new Envelope(
            from: new Address($delivery['address'], $brandName),
            replyTo: [new Address($delivery['address'], $brandName)],
            subject: 'You are invited to register on TICH ERP',
            using: [
                static function (SymfonyEmail $message) use ($delivery, $domain): void {
                    $message->returnPath($delivery['address']);
                    $message->sender($delivery['address']);
                    $message->getHeaders()->addIdHeader('Message-ID', Str::uuid()->toString().'@'.$domain);
                },
            ],
        );
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

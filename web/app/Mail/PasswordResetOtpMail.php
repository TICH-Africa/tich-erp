<?php

namespace App\Mail;

use App\Mail\Concerns\UsesModuleEnvelope;
use App\Support\ModuleMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email as SymfonyEmail;

class PasswordResetOtpMail extends Mailable
{
    use Queueable, SerializesModels, UsesModuleEnvelope;

    public function __construct(
        public string $otp,
        public int $expiresMinutes = 15,
    ) {}

    protected function mailModule(): string
    {
        // Same proven path as registration invites — ict@ does not reach external inboxes.
        return ModuleMail::NOTIFICATION;
    }

    public function envelope(): Envelope
    {
        $delivery = ModuleMail::from(ModuleMail::NOTIFICATION);
        $brand = ModuleMail::from(ModuleMail::ICT);
        $domain = substr(strrchr($delivery['address'], '@') ?: '@tich.africa', 1) ?: 'tich.africa';

        return new Envelope(
            from: new Address($delivery['address'], $brand['name']),
            replyTo: [new Address($delivery['address'], $brand['name'])],
            subject: 'TICH ERP - Password reset code',
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
            view: 'emails.password-reset-otp',
        );
    }
}

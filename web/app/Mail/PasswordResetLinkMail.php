<?php

namespace App\Mail;

use App\Mail\Concerns\UsesModuleEnvelope;
use App\Support\ModuleMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetLinkMail extends Mailable
{
    use Queueable, SerializesModels, UsesModuleEnvelope;

    public function __construct(
        public string $resetUrl,
        public int $expiresMinutes = 60,
    ) {}

    protected function mailModule(): string
    {
        return ModuleMail::OTP;
    }

    public function envelope(): Envelope
    {
        return $this->moduleEnvelope('TICH ERP - Password reset');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset-link',
        );
    }
}

<?php

namespace App\Mail;

use App\Mail\Concerns\UsesModuleEnvelope;
use App\Support\ModuleMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetOtpMail extends Mailable
{
    use Queueable, SerializesModels, UsesModuleEnvelope;

    public function __construct(
        public string $otp,
        public int $expiresMinutes = 15,
    ) {}

    protected function mailModule(): string
    {
        return ModuleMail::ICT;
    }

    public function envelope(): Envelope
    {
        return $this->moduleEnvelope('TICH ERP - Password reset code');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset-otp',
        );
    }
}

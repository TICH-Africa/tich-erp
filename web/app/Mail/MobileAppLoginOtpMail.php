<?php

namespace App\Mail;

use App\Mail\Concerns\UsesModuleEnvelope;
use App\Support\ModuleMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MobileAppLoginOtpMail extends Mailable
{
    use Queueable, SerializesModels, UsesModuleEnvelope;

    public function __construct(
        public string $otp,
        public int $expiresMinutes = 15,
    ) {}

    protected function mailModule(): string
    {
        return ModuleMail::OTP;
    }

    public function envelope(): Envelope
    {
        return $this->moduleEnvelope('TICH Mobile – Sign-in code');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.mobile-app-login-otp',
        );
    }
}
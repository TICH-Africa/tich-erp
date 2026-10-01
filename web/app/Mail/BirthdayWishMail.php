<?php

namespace App\Mail;

use App\Mail\Concerns\UsesModuleEnvelope;
use App\Support\ModuleMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BirthdayWishMail extends Mailable
{
    use Queueable, SerializesModels, UsesModuleEnvelope;

    public function __construct(
        public string $recipientName,
        public string $audienceLabel = 'community member',
    ) {}

    protected function mailModule(): string
    {
        return ModuleMail::NOTIFICATION;
    }

    public function envelope(): Envelope
    {
        return $this->moduleEnvelope('Happy Birthday from TICH!');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.birthday-wish',
        );
    }
}

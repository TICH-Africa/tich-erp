<?php

namespace App\Mail;

use App\Mail\Concerns\UsesModuleEnvelope;
use App\Support\ModuleMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlatformNotificationMail extends Mailable
{
    use Queueable, SerializesModels, UsesModuleEnvelope;

    public function __construct(
        public string $notificationTitle,
        public string $notificationBody,
        public string $priority = 'normal',
        public ?string $actionUrl = null,
    ) {}

    protected function mailModule(): string
    {
        return ModuleMail::NOTIFICATION;
    }

    public function envelope(): Envelope
    {
        return $this->moduleEnvelope($this->notificationTitle);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.platform-notification',
        );
    }
}

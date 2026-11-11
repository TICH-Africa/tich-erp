<?php

namespace App\Mail;

use App\Mail\Concerns\UsesModuleEnvelope;
use App\Support\ModuleMail;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WorkAnniversaryMail extends Mailable
{
    use Queueable, SerializesModels, UsesModuleEnvelope;

    public function __construct(
        public string $recipientName,
        public int $yearsCompleted,
        public CarbonInterface $employmentStartDate,
    ) {}

    protected function mailModule(): string
    {
        return ModuleMail::NOTIFICATION;
    }

    public function envelope(): Envelope
    {
        $years = $this->yearsCompleted === 1
            ? '1 year'
            : "{$this->yearsCompleted} years";

        return $this->moduleEnvelope("Celebrating {$years} with TICH!");
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.work-anniversary',
        );
    }
}

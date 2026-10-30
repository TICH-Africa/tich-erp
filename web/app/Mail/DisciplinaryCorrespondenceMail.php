<?php

namespace App\Mail;

use App\Mail\Concerns\UsesModuleEnvelope;
use App\Models\DisciplinaryCase;
use App\Models\Staff;
use App\Support\ModuleMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DisciplinaryCorrespondenceMail extends Mailable
{
    use Queueable, SerializesModels, UsesModuleEnvelope;

    public function __construct(
        public DisciplinaryCase $case,
        public Staff $staff,
        public string $mailSubject,
        public string $bodyText,
    ) {}

    protected function mailModule(): string
    {
        return ModuleMail::HR;
    }

    public function envelope(): Envelope
    {
        return $this->moduleEnvelope($this->mailSubject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.disciplinary-correspondence',
        );
    }
}

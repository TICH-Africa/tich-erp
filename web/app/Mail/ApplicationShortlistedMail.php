<?php

namespace App\Mail;

use App\Mail\Concerns\UsesModuleEnvelope;
use App\Models\Applicant;
use App\Support\ModuleMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationShortlistedMail extends Mailable
{
    use Queueable, SerializesModels, UsesModuleEnvelope;

    public function __construct(
        public Applicant $applicant,
        public string $programName,
        public string $statusUrl,
        public string $admissionFeeNotice,
        public float $feeAmount = 0,
        public string $accountReference = '',
        public string $payUrl = '',
    ) {}

    protected function mailModule(): string
    {
        return ModuleMail::ACADEMICS;
    }

    public function envelope(): Envelope
    {
        return $this->moduleEnvelope('TICH - Application shortlisted ('.$this->applicant->application_number.')');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.application-shortlisted',
        );
    }
}

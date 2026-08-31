<?php

namespace App\Mail;

use App\Mail\Concerns\UsesModuleEnvelope;
use App\Models\Applicant;
use App\Support\ModuleMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationAcademicApprovedMail extends Mailable
{
    use Queueable, SerializesModels, UsesModuleEnvelope;

    /**
     * @param  list<string>  $feeStructureLines
     * @param  array{path: string, filename: string}|null  $applicationLetter
     */
    public function __construct(
        public Applicant $applicant,
        public string $programName,
        public string $statusUrl,
        public string $admissionFeeNotice,
        public float $feeAmount = 0,
        public string $accountReference = '',
        public string $payUrl = '',
        public array $feeStructureLines = [],
        public ?array $applicationLetter = null,
    ) {}

    protected function mailModule(): string
    {
        return ModuleMail::ACADEMICS;
    }

    public function envelope(): Envelope
    {
        return $this->moduleEnvelope('TICH - Application approved ('.$this->applicant->application_number.')');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.application-academic-approved',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if (! $this->applicationLetter) {
            return [];
        }

        return [
            Attachment::fromStorageDisk('public', $this->applicationLetter['path'])
                ->as($this->applicationLetter['filename']),
        ];
    }
}

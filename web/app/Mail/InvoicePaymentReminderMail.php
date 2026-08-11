<?php

namespace App\Mail;

use App\Mail\Concerns\UsesModuleEnvelope;
use App\Models\Invoice;
use App\Support\ModuleMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoicePaymentReminderMail extends Mailable
{
    use Queueable, SerializesModels, UsesModuleEnvelope;

    public function __construct(
        public Invoice $invoice,
    ) {}

    protected function mailModule(): string
    {
        return ModuleMail::FINANCE;
    }

    public function envelope(): Envelope
    {
        return $this->moduleEnvelope('TICH - Payment reminder: '.$this->invoice->invoice_number);
    }

    public function content(): Content
    {
        $this->invoice->loadMissing(['student.applicant', 'student.program']);

        return new Content(
            view: 'emails.finance.invoice-payment-reminder',
        );
    }
}

<?php

namespace App\Mail;

use App\Mail\Concerns\UsesModuleEnvelope;
use App\Models\Payment;
use App\Support\ModuleMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentConfirmationMail extends Mailable
{
    use Queueable, SerializesModels, UsesModuleEnvelope;

    public function __construct(
        public Payment $payment,
    ) {}

    protected function mailModule(): string
    {
        return ModuleMail::FINANCE;
    }

    public function envelope(): Envelope
    {
        return $this->moduleEnvelope('TICH - Payment received ('.$this->payment->payment_number.')');
    }

    public function content(): Content
    {
        $this->payment->loadMissing(['invoice', 'student.applicant']);

        return new Content(
            view: 'emails.finance.payment-confirmation',
        );
    }
}

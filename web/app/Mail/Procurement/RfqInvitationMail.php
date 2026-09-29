<?php

namespace App\Mail\Procurement;

use App\Models\Rfq;
use App\Models\RfqSupplier;
use App\Models\Supplier;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RfqInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Rfq $rfq,
        public Supplier $supplier,
        public RfqSupplier $invitation
    ) {}

    public function build(): static
    {
        return $this->subject("Invitation to Quote: {$this->rfq->rfq_number}")
            ->view('emails.procurement.rfq-invitation');
    }
}
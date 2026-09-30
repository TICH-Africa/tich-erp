<?php

namespace App\Mail;

use App\Mail\Concerns\UsesModuleEnvelope;
use App\Models\Staff;
use App\Models\StaffContract;
use App\Support\ModuleMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContractSignedEmail extends Mailable
{
    use Queueable, SerializesModels, UsesModuleEnvelope;

    public function __construct(
        public Staff $staff,
        public StaffContract $contract,
    ) {}

    protected function mailModule(): string
    {
        return ModuleMail::HR;
    }

    public function envelope(): Envelope
    {
        return $this->moduleEnvelope('Your employment contract has been signed - TICH');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contract-signed',
            with: [
                'staff' => $this->staff,
                'contract' => $this->contract,
            ],
        );
    }
}

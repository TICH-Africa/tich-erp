<?php

namespace App\Mail\Concerns;

use App\Support\ModuleMail;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;

trait UsesModuleEnvelope
{
    abstract protected function mailModule(): string;

    protected function moduleEnvelope(string $subject): Envelope
    {
        $from = ModuleMail::from($this->mailModule());

        return new Envelope(
            from: new Address($from['address'], $from['name']),
            subject: $subject,
        );
    }
}

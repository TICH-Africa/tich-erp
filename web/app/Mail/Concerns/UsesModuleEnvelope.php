<?php

namespace App\Mail\Concerns;

use App\Support\ModuleMail;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email as SymfonyEmail;

trait UsesModuleEnvelope
{
    abstract protected function mailModule(): string;

    protected function moduleEnvelope(string $subject): Envelope
    {
        $from = ModuleMail::from($this->mailModule());
        $domain = self::domainFromAddress($from['address']);

        return new Envelope(
            from: new Address($from['address'], $from['name']),
            replyTo: [new Address($from['address'], $from['name'])],
            subject: $subject,
            using: [
                static function (SymfonyEmail $message) use ($from, $domain): void {
                    $message->returnPath($from['address']);
                    $message->sender($from['address']);
                    $message->getHeaders()->addIdHeader('Message-ID', Str::uuid()->toString().'@'.$domain);
                },
            ],
        );
    }

    private static function domainFromAddress(string $address): string
    {
        $at = strrpos($address, '@');
        if ($at === false) {
            return 'tich.africa';
        }

        $domain = strtolower(substr($address, $at + 1));

        return $domain !== '' ? $domain : 'tich.africa';
    }
}

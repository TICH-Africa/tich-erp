<?php

namespace App\Support;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ModuleMail
{
    public const ICT = 'ict';

    public const HR = 'hr';

    public const ACADEMICS = 'academics';

    public const FINANCE = 'finance';

    public const OTP = 'otp';

    public const NOTIFICATION = 'notification';

    public static function mailer(string $module): string
    {
        return (string) config("tich-mail.modules.{$module}.mailer", config('mail.default'));
    }

    /**
     * @return array{address: string, name: string}
     */
    public static function from(string $module): array
    {
        /** @var array{address?: string, name?: string} $from */
        $from = config("tich-mail.modules.{$module}.from", config('mail.from'));

        return [
            'address' => (string) ($from['address'] ?? config('mail.from.address')),
            'name' => (string) ($from['name'] ?? config('mail.from.name')),
        ];
    }

    public static function send(string $module, string $to, Mailable $mailable): void
    {
        Mail::mailer(self::mailer($module))->to($to)->send($mailable);
    }

    /**
     * @return array{sent: bool, error: ?string}
     */
    public static function trySend(string $module, string $to, Mailable $mailable): array
    {
        if ($issue = MailConfig::moduleIssue($module)) {
            return ['sent' => false, 'error' => $issue];
        }

        try {
            self::send($module, $to, $mailable);

            return ['sent' => true, 'error' => null];
        } catch (Throwable $e) {
            return [
                'sent' => false,
                'error' => MailConfig::friendlySmtpError($e->getMessage(), $module),
            ];
        }
    }

    public static function isConfigured(string $module): bool
    {
        return MailConfig::moduleIssue($module) === null;
    }
}

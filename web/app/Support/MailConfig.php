<?php

namespace App\Support;

class MailConfig
{
    /** @var list<string> */
    private const PLACEHOLDER_PASSWORDS = [
        'suggeststrongpassword',
        'your-app-password',
        'changeme',
        'password',
    ];

    public static function smtpPasswordIssue(): ?string
    {
        if (config('mail.default') !== 'smtp') {
            return null;
        }

        $password = config('mail.mailers.smtp.password');

        if ($password === null || $password === '') {
            return 'MAIL_PASSWORD is not set. Generate a Google App Password at https://myaccount.google.com/apppasswords and add the 16-character value to .env (no spaces).';
        }

        if (in_array(strtolower((string) $password), self::PLACEHOLDER_PASSWORDS, true)) {
            return 'MAIL_PASSWORD is still a placeholder. Gmail requires a Google App Password — not your normal login password. Create one at https://myaccount.google.com/apppasswords for tichinafricaict@gmail.com, then run: php artisan config:clear';
        }

        return null;
    }

    public static function friendlySmtpError(string $message): string
    {
        if (str_contains($message, '535') || str_contains($message, 'BadCredentials')) {
            return 'Gmail rejected the SMTP login. Enable 2-Step Verification on tichinafricaict@gmail.com, create an App Password at https://myaccount.google.com/apppasswords, set MAIL_PASSWORD in .env to that 16-character password (no spaces), then run php artisan config:clear.';
        }

        return $message;
    }
}

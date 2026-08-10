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
        return self::moduleIssue((string) config('tich-mail.default_module', 'notification'));
    }

    public static function moduleIssue(string $module): ?string
    {
        if (! config()->has("tich-mail.modules.{$module}")) {
            return "Unknown mail module [{$module}].";
        }

        if (in_array(config('mail.default'), ['log', 'array'], true)) {
            return null;
        }

        $mailer = ModuleMail::mailer($module);
        $mailerConfig = config("mail.mailers.{$mailer}");

        if (! is_array($mailerConfig) || ($mailerConfig['transport'] ?? null) !== 'smtp') {
            return null;
        }

        $address = ModuleMail::from($module)['address'];
        $password = $mailerConfig['password'] ?? null;
        $envPrefix = strtoupper($module);

        if ($address === '') {
            return "MAIL_{$envPrefix}_ADDRESS is not set in .env.";
        }

        if ($password === null || $password === '') {
            return "MAIL_{$envPrefix}_PASSWORD is not set in .env.";
        }

        if (in_array(strtolower((string) $password), self::PLACEHOLDER_PASSWORDS, true)) {
            return "MAIL_{$envPrefix}_PASSWORD is still a placeholder in .env.";
        }

        if (empty($mailerConfig['host'])) {
            return 'MAIL_HOST is not set in .env.';
        }

        return null;
    }

    public static function friendlySmtpError(string $message, ?string $module = null): string
    {
        if (str_contains($message, '535') || str_contains($message, 'BadCredentials')) {
            $module ??= (string) config('tich-mail.default_module', 'notification');
            $username = ModuleMail::from($module)['address'] ?: config('mail.mailers.smtp.username', 'your mailbox');

            return "SMTP login rejected for {$username}. Check MAIL_{$module} credentials in .env, then run: php artisan config:clear";
        }

        return $message;
    }
}

<?php

namespace App\Support;

class MailPublicUrl
{
    /**
     * Base URL for links inside outbound emails.
     * Private/local APP_URL hosts are avoided — Gmail and others often drop those messages.
     */
    public static function base(): string
    {
        $configured = config('tich-mail.link_url');
        if (is_string($configured) && trim($configured) !== '') {
            return rtrim(trim($configured), '/');
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl !== '' && ! self::isNonPublicHost($appUrl)) {
            return $appUrl;
        }

        return 'https://tich.africa';
    }

    public static function to(string $path): string
    {
        return self::base().'/'.ltrim($path, '/');
    }

    public static function isNonPublicHost(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return true;
        }

        $host = strtolower($host);

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return true;
        }

        if (str_ends_with($host, '.local') || str_ends_with($host, '.test') || str_ends_with($host, '.invalid')) {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return ! filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        return false;
    }
}

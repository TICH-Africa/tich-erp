<?php

namespace App\Support;

use Illuminate\Support\Facades\Request;

class NotificationUrl
{
    /**
     * A link minted by a console run, a queue worker, or another port cannot be opened, so
     * loopback links are re-pointed at the address this app is actually reached from.
     */
    public static function sameApp(?string $url): ?string
    {
        if ($url === null || $url === '' || preg_match('#^https?://#i', $url) !== 1) {
            return $url;
        }

        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));

        if (! in_array($host, ['localhost', '127.0.0.1', '::1', '[::1]', '0.0.0.0'], true)) {
            return $url;
        }

        $root = self::root();
        if ($root === '') {
            return $url;
        }

        $path = ($parts['path'] ?? '') === '' ? '/' : $parts['path'];

        return $root.$path
            .(isset($parts['query']) ? '?'.$parts['query'] : '')
            .(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
    }

    /**
     * The origin a reader can actually load: the address of the current request, or APP_URL
     * when no request is serving (console commands, scheduled alerts, queue workers).
     */
    private static function root(): string
    {
        $candidates = [];

        if (! app()->runningInConsole()) {
            $candidates[] = rtrim((string) Request::root(), '/');
        }

        $candidates[] = rtrim((string) config('app.url'), '/');

        foreach ($candidates as $candidate) {
            if ($candidate !== '' && preg_match('#^https?://#i', $candidate) === 1) {
                return $candidate;
            }
        }

        return '';
    }
}

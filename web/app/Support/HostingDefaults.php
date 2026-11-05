<?php

namespace App\Support;

/**
 * Safer defaults for shared hosting (fewer MySQL connections per HTTP request).
 */
final class HostingDefaults
{
    public static function sessionDriver(): string
    {
        $configured = env('SESSION_DRIVER');
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        return env('APP_ENV') === 'production' ? 'file' : 'database';
    }

    public static function cacheStore(): string
    {
        $configured = env('CACHE_STORE');
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        return env('APP_ENV') === 'production' ? 'file' : 'database';
    }

    public static function usesDatabaseForSessionOrCache(): bool
    {
        return self::sessionDriver() === 'database'
            || self::cacheStore() === 'database';
    }
}

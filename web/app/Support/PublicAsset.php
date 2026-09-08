<?php

namespace App\Support;

/**
 * Resolves public CSS/JS/image/storage URLs using Laravel's asset root
 * (APP_URL / ASSET_URL), with cache-busting for files under /public.
 */
class PublicAsset
{
    /**
     * URL for a file under public/ (css, js, images, …).
     */
    public static function url(string $path): string
    {
        $relative = self::normalizePublicPath($path);
        $url = asset($relative);
        $absolute = public_path($relative);

        if (is_file($absolute)) {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator.'v='.filemtime($absolute);
        }

        return $url;
    }

    /**
     * URL for an uploaded/public disk file (storage/app/public → /storage/…).
     * Accepts "storage/…", relative disk paths, or absolute http(s) URLs.
     * Returns a host-relative path when possible so 127.0.0.1 vs LAN IP both work.
     */
    public static function media(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $normalized = str_replace('\\', '/', trim($path));

        if (str_starts_with($normalized, 'http://') || str_starts_with($normalized, 'https://')) {
            return self::toHostRelative($normalized) ?? $normalized;
        }

        // Already a public web path.
        if (str_starts_with($normalized, 'storage/')
            || str_starts_with($normalized, 'images/')
            || str_starts_with($normalized, 'css/')
            || str_starts_with($normalized, 'js/')) {
            return '/'.ltrim($normalized, '/');
        }

        $relative = ltrim($normalized, '/');
        if (str_starts_with($relative, 'public/')) {
            $relative = substr($relative, 7);
        }

        if (str_starts_with($relative, 'storage/')) {
            return '/'.$relative;
        }

        return '/storage/'.$relative;
    }

    private static function toHostRelative(string $url): ?string
    {
        $parsed = parse_url($url);
        if ($parsed === false || empty($parsed['path'])) {
            return null;
        }

        return $parsed['path'].(isset($parsed['query']) ? '?'.$parsed['query'] : '');
    }

    private static function normalizePublicPath(string $path): string
    {
        $relative = ltrim(str_replace('\\', '/', $path), '/');

        if (str_starts_with($relative, 'public/')) {
            $relative = substr($relative, 7);
        }

        return $relative;
    }
}

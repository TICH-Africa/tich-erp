<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Seo
{
    /**
     * @param  array{
     *   title?: string,
     *   description?: string|null,
     *   image?: string|null,
     *   url?: string|null,
     *   type?: string|null,
     *   robots?: string|null,
     *   site_name?: string|null,
     *   published_time?: string|null,
     *   modified_time?: string|null,
     * }  $overrides
     * @param  array<string, mixed>  $siteMeta
     * @return array<string, mixed>
     */
    public static function build(array $overrides, array $siteMeta = []): array
    {
        $defaults = config('tich-seo.defaults', []);
        $tagline = (string) ($siteMeta['tagline'] ?? '');
        $metaDescription = (string) ($siteMeta['meta_description'] ?? $tagline);
        $institution = (string) ($siteMeta['institution_name'] ?? $siteMeta['short_name'] ?? config('app.name'));

        $image = $overrides['image']
            ?? $siteMeta['og_image_url']
            ?? $siteMeta['logo_url']
            ?? PublicAsset::url('images/logo.png');

        $image = self::absoluteUrl($image) ?? PublicAsset::url('images/logo.png');
        $imageMeta = self::imageMeta($image);

        $description = trim((string) ($overrides['description'] ?? $metaDescription));
        if ($description === '') {
            $description = $institution.' — community health education for Africa.';
        }
        $description = Str::limit(preg_replace('/\s+/', ' ', strip_tags($description)) ?? $description, 160, '');

        $title = trim((string) ($overrides['title'] ?? 'Home'));

        return [
            'title' => $title,
            'full_title' => $title.' - '.($siteMeta['short_name'] ?? config('app.name')),
            'description' => $description,
            'image' => $image,
            'image_width' => $imageMeta['width'] ?? null,
            'image_height' => $imageMeta['height'] ?? null,
            'image_alt' => $overrides['image_alt'] ?? ($siteMeta['institution_name'] ?? $institution),
            'url' => self::absoluteUrl($overrides['url'] ?? url()->current()) ?? url()->current(),
            'type' => $overrides['type'] ?? ($defaults['og_type'] ?? 'website'),
            'robots' => $overrides['robots'] ?? ($defaults['robots'] ?? 'index,follow'),
            'site_name' => $overrides['site_name'] ?? ($siteMeta['institution_name'] ?? $institution),
            'twitter_card' => $defaults['twitter_card'] ?? 'summary_large_image',
            'locale' => str_replace('_', '-', app()->getLocale()),
            'published_time' => $overrides['published_time'] ?? null,
            'modified_time' => $overrides['modified_time'] ?? null,
        ];
    }

    public static function pageDefaults(string $pageKey): array
    {
        return config("tich-seo.pages.{$pageKey}", []);
    }

    public static function absoluteUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $url = trim($url);

        if (str_starts_with($url, '//')) {
            $url = 'https:'.$url;
        }

        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = PublicAsset::media($url) ?? asset(ltrim($url, '/'));
        }

        if (str_starts_with($url, 'http://')) {
            $url = 'https://'.substr($url, 7);
        }

        return $url;
    }

    /**
     * @return array{url: string, width?: int, height?: int}|null
     */
    public static function imageObject(?string $url, ?string $alt = null): ?array
    {
        $absolute = self::absoluteUrl($url);

        if ($absolute === null) {
            return null;
        }

        $meta = self::imageMeta($absolute);
        $object = [
            '@type' => 'ImageObject',
            'url' => $absolute,
        ];

        if (isset($meta['width'], $meta['height'])) {
            $object['width'] = $meta['width'];
            $object['height'] = $meta['height'];
        }

        if ($alt !== null && $alt !== '') {
            $object['caption'] = $alt;
        }

        return $object;
    }

    /**
     * @return array{width: int, height: int}|null
     */
    public static function imageMeta(?string $absoluteUrl): ?array
    {
        $path = self::localPathForUrl($absoluteUrl);

        if ($path === null || ! is_file($path)) {
            return null;
        }

        $size = @getimagesize($path);

        if ($size === false || ! isset($size[0], $size[1])) {
            return null;
        }

        return [
            'width' => (int) $size[0],
            'height' => (int) $size[1],
        ];
    }

    public static function localPathForUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return null;
        }

        $relative = ltrim(rawurldecode($path), '/');

        if (str_starts_with($relative, 'storage/')) {
            $diskPath = substr($relative, strlen('storage/'));

            if ($diskPath !== '' && Storage::disk('public')->exists($diskPath)) {
                return Storage::disk('public')->path($diskPath);
            }
        }

        $publicPath = public_path($relative);

        return is_file($publicPath) ? $publicPath : null;
    }
}

<?php

namespace App\Support;

use App\Support\PublicAsset;
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

        if (is_string($image) && $image !== '' && ! str_starts_with($image, 'http')) {
            $image = PublicAsset::media($image) ?? url($image);
        }

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
            'url' => $overrides['url'] ?? url()->current(),
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
}

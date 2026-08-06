<?php

namespace App\Support;

class NavIcon
{
    public static function forItem(array $item): string
    {
        if (! empty($item['icon_name'])) {
            return $item['icon_name'];
        }

        if (! empty($item['icon'])) {
            return $item['icon'];
        }

        $label = trim($item['label'] ?? '');

        if ($label !== '') {
            $byLabel = config('tich-navigation-icons.labels', []);

            if (isset($byLabel[$label])) {
                return $byLabel[$label];
            }
        }

        $url = $item['url'] ?? $item['url_or_route'] ?? '';

        if ($url !== '') {
            $path = parse_url($url, PHP_URL_PATH) ?? '';

            if ($path !== '') {
                $paths = config('tich-navigation-icons.paths', []);

                if (isset($paths[$path])) {
                    return $paths[$path];
                }
            }
        }

        return config('tich-navigation-icons.default', 'circle');
    }
}

<?php

namespace App\Services;

use App\Models\Site\ContactChannel;
use App\Models\Site\NavigationMenu;
use App\Models\Site\SocialLink;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class NavigationService
{
    public function getHeaderMenu(): array
    {
        return $this->resolveMenu('header', config('tich-navigation.header', []));
    }

    public function getFooterPrimaryMenu(): array
    {
        return $this->resolveMenu('footer_primary', config('tich-navigation.footer_primary', []));
    }

    public function getFooterQuickLinks(): array
    {
        return $this->resolveMenu('footer_quick_links', config('tich-navigation.footer_quick_links', []));
    }

    public function getContactChannels(): array
    {
        if ($this->tableExists('contact_channels')) {
            $channels = ContactChannel::query()
                ->where('is_active', 1)
                ->orderByDesc('is_primary')
                ->orderBy('display_order')
                ->get();

            if ($channels->isNotEmpty()) {
                return $channels->map(fn ($channel) => [
                    'channel_type' => $channel->channel_type,
                    'label' => $channel->label,
                    'display_value' => $channel->display_value ?? $channel->value,
                    'value' => $channel->value,
                    'href' => $this->contactHref($channel->channel_type, $channel->value),
                ])->all();
            }
        }

        return collect(config('tich-navigation.contact', []))
            ->map(fn ($item) => array_merge($item, [
                'href' => $this->contactHref($item['channel_type'] ?? '', $item['value'] ?? $item['display_value'] ?? ''),
            ]))
            ->all();
    }

    public function getSocialLinks(): array
    {
        if ($this->tableExists('social_links')) {
            $links = SocialLink::query()
                ->where('is_active', 1)
                ->orderBy('display_order')
                ->get();

            if ($links->isNotEmpty()) {
                return $links->map(fn ($link) => [
                    'platform' => $link->platform,
                    'display_name' => $link->display_name,
                    'url' => $link->url,
                    'icon_name' => $link->icon_name ?? $link->platform,
                ])->all();
            }
        }

        return config('tich-navigation.social', []);
    }

    public function getSiteMeta(): array
    {
        return app(SiteSettingsService::class)->siteMeta();
    }

    public function resolveUrl(?string $urlOrRoute): string
    {
        if (! $urlOrRoute) {
            return '#';
        }

        if (str_starts_with($urlOrRoute, 'route:')) {
            $routeName = substr($urlOrRoute, 6);

            return route($routeName, absolute: false);
        }

        if (str_starts_with($urlOrRoute, 'http://') || str_starts_with($urlOrRoute, 'https://')) {
            return $urlOrRoute;
        }

        if (str_starts_with($urlOrRoute, '/')) {
            return url($urlOrRoute);
        }

        if (str_starts_with($urlOrRoute, '#')) {
            return $urlOrRoute;
        }

        return url('/'.$urlOrRoute);
    }

    public function itemVisible(array $item): bool
    {
        if (! empty($item['requires_auth']) && ! auth()->check()) {
            return false;
        }

        if (! empty($item['allowed_user_types']) && auth()->check()) {
            $allowed = is_array($item['allowed_user_types'])
                ? $item['allowed_user_types']
                : json_decode($item['allowed_user_types'], true);

            if ($allowed && ! in_array(auth()->user()->user_type, $allowed, true)) {
                return false;
            }
        }

        return true;
    }

    private function resolveMenu(string $location, array $fallback): array
    {
        if (! $this->tableExists('navigation_menus')) {
            return $this->normalizeFallback($fallback);
        }

        $menu = NavigationMenu::query()
            ->where('location', $location)
            ->where('is_active', 1)
            ->orderBy('display_order')
            ->with(['items.children'])
            ->first();

        if (! $menu || $menu->items->isEmpty()) {
            return $this->ensureCareersLink($location, $this->normalizeFallback($fallback));
        }

        return $this->ensureCareersLink($location, $menu->items
            ->map(fn ($item) => $this->normalizeNavItem($this->mapDbItem($item)))
            ->filter(fn ($item) => $this->itemVisible($item))
            ->values()
            ->all());
    }

    private function ensureCareersLink(string $location, array $items): array
    {
        if ($location !== 'header') {
            return $items;
        }

        $hasCareers = collect($items)->contains(fn ($item) => ($item['label'] ?? '') === 'Careers');

        if ($hasCareers) {
            return collect($items)->map(function ($item) {
                if (($item['label'] ?? '') === 'Careers') {
                    $item['url_or_route'] = '/careers';
                    $item['url'] = $this->resolveUrl('/careers');
                }

                return $item;
            })->all();
        }

        $items[] = $this->normalizeNavItem([
            'label' => 'Careers',
            'url_or_route' => '/careers',
            'url' => $this->resolveUrl('/careers'),
            'target' => 'self',
            'requires_auth' => false,
            'allowed_user_types' => null,
            'children' => [],
        ]);

        return $items;
    }

    private function normalizeNavItem(array $item): array
    {
        $legacyLabels = ['Admissions', 'Admissions Guide', 'Programs/Courses', 'Programs & Courses'];

        if (in_array($item['label'], $legacyLabels, true)) {
            $item['label'] = 'Programs & courses';
            $item['url_or_route'] = '/programs';
            $item['url'] = route('programs.index', absolute: false);
        }

        if (! empty($item['children'])) {
            $item['children'] = collect($item['children'])
                ->map(fn ($child) => $this->normalizeNavItem($child))
                ->all();
        }

        return $item;
    }

    private function mapDbItem($item): array
    {
        $mapped = [
            'label' => $item->label,
            'label_sw' => $item->label_sw,
            'url' => $this->resolveUrl($item->url_or_route),
            'url_or_route' => $item->url_or_route,
            'target' => $item->target ?? 'self',
            'icon_name' => $item->icon_name,
            'requires_auth' => (bool) $item->requires_auth,
            'allowed_user_types' => $item->allowed_user_types,
            'children' => [],
        ];

        if ($item->relationLoaded('children') && $item->children->isNotEmpty()) {
            $mapped['children'] = $item->children
                ->map(fn ($child) => $this->mapDbItem($child))
                ->filter(fn ($child) => $this->itemVisible($child))
                ->values()
                ->all();
        }

        return $mapped;
    }

    private function normalizeFallback(array $items): array
    {
        return collect($items)
            ->map(function ($item) {
                $url = $item['url'] ?? $item['url_or_route'] ?? '#';

                return $this->normalizeNavItem([
                    'label' => $item['label'],
                    'label_sw' => $item['label_sw'] ?? null,
                    'url' => $this->resolveUrl($url),
                    'url_or_route' => $url,
                    'target' => $item['target'] ?? 'self',
                    'icon_name' => $item['icon_name'] ?? $item['icon'] ?? null,
                    'requires_auth' => $item['requires_auth'] ?? false,
                    'allowed_user_types' => $item['allowed_user_types'] ?? null,
                    'children' => $item['children'] ?? [],
                ]);
            })
            ->filter(fn ($item) => $this->itemVisible($item))
            ->values()
            ->all();
    }

    private function contactHref(string $type, string $value): string
    {
        return match ($type) {
            'email' => 'mailto:'.$value,
            'phone' => 'tel:'.preg_replace('/\s+/', '', $value),
            default => '#',
        };
    }

    private function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }
}

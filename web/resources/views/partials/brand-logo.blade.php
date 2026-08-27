@php
    $siteMeta = $siteMeta ?? app(\App\Services\SiteSettingsService::class)->siteMeta();
    $static = $static ?? false;
    $brandClass = 'tich-brand '.(($variant ?? 'default') === 'light' ? 'tich-brand--light' : '');
    $hasCircularMark = is_file(public_path('images/logo-mark.png'));
    $logoSrc = $hasCircularMark
        ? asset('images/logo-mark.png').'?v='.filemtime(public_path('images/logo-mark.png'))
        : ($siteMeta['logo_url'] ?? asset('images/logo.png'));
    $logoClass = 'tich-brand__logo'.($hasCircularMark ? ' tich-brand__logo--circle' : '');
@endphp
@if ($static)
    <div class="{{ $brandClass }}" aria-hidden="true">
@else
    <a href="{{ route('home') }}" class="{{ $brandClass }}">
@endif
    <img
        src="{{ $logoSrc }}"
        alt="{{ $siteMeta['brand_name'] ?? $siteMeta['short_name'] ?? 'TICH ERP' }}"
        class="{{ $logoClass }}"
        data-lazy="eager"
        fetchpriority="high"
        width="40"
        height="40"
    >
    <div>
        <p class="tich-brand__name">{{ $siteMeta['brand_name'] ?? $siteMeta['short_name'] ?? 'TICH ERP' }}</p>
        @if (!empty($siteMeta['brand_tagline']))
            <p class="tich-brand__tagline">{{ $siteMeta['brand_tagline'] }}</p>
        @endif
    </div>
@if ($static)
    </div>
@else
    </a>
@endif

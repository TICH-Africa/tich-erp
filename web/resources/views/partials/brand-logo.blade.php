@php
    $siteMeta = $siteMeta ?? app(\App\Services\SiteSettingsService::class)->siteMeta();
    $static = $static ?? false;
    $brandClass = 'tich-brand '.(($variant ?? 'default') === 'light' ? 'tich-brand--light' : '');
@endphp
@if ($static)
    <div class="{{ $brandClass }}" aria-hidden="true">
@else
    <a href="{{ route('home') }}" class="{{ $brandClass }}">
@endif
    @if (!empty($siteMeta['logo_url']))
        <img src="{{ $siteMeta['logo_url'] }}" alt="{{ $siteMeta['brand_name'] ?? $siteMeta['short_name'] ?? 'Home' }}" class="tich-brand__logo" data-lazy="eager" fetchpriority="high" style="max-height: 40px; max-width: 140px; object-fit: contain;">
    @else
        <img src="{{ asset('images/logo.png') }}" alt="{{ $siteMeta['brand_name'] ?? $siteMeta['short_name'] ?? 'TICH ERP' }}" class="tich-brand__logo" data-lazy="eager" fetchpriority="high" style="max-height: 40px; max-width: 140px; object-fit: contain;">
    @endif
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

@php
    $siteMeta = $siteMeta ?? app(\App\Services\SiteSettingsService::class)->siteMeta();
@endphp
<a href="{{ route('home') }}" class="tich-brand {{ ($variant ?? 'default') === 'light' ? 'tich-brand--light' : '' }}">
    @if (!empty($siteMeta['logo_url']))
        <img src="{{ $siteMeta['logo_url'] }}" alt="{{ $siteMeta['brand_name'] ?? $siteMeta['short_name'] ?? 'Home' }}" class="tich-brand__logo" style="max-height: 40px; max-width: 140px; object-fit: contain;">
    @else
        <span class="tich-brand__mark">{{ strtoupper(substr($siteMeta['brand_name'] ?? $siteMeta['short_name'] ?? 'T', 0, 1)) }}</span>
    @endif
    <div>
        <p class="tich-brand__name">{{ $siteMeta['brand_name'] ?? $siteMeta['short_name'] ?? 'TICH ERP' }}</p>
        @if (!empty($siteMeta['brand_tagline']))
            <p class="tich-brand__tagline">{{ $siteMeta['brand_tagline'] }}</p>
        @endif
    </div>
</a>

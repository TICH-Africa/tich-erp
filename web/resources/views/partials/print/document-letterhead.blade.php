<header class="tich-doc-letterhead">
    @if (! empty($institution['logo_src']))
        <img
            src="{{ $institution['logo_src'] }}"
            alt="{{ $institution['short_name'] ?? 'Institution logo' }}"
            class="tich-doc-letterhead__logo"
        >
    @else
        <div class="tich-doc-letterhead__mark" aria-hidden="true">{{ $institution['brand_initial'] ?? 'T' }}</div>
    @endif
    <div>
        <p class="tich-doc-letterhead__name">{{ $institution['name'] ?? 'TICH in Africa' }}</p>
        <p class="tich-doc-letterhead__tagline">{{ $institution['tagline'] ?? '' }}</p>
        @if (! empty($institution['address']))
            <p class="tich-doc-letterhead__address">{{ $institution['address'] }}</p>
        @endif
    </div>
</header>

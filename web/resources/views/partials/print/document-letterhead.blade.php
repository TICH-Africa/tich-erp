<header class="tich-doc-letterhead">
    <div class="tich-doc-letterhead__mark" aria-hidden="true">T</div>
    <div>
        <p class="tich-doc-letterhead__name">{{ $institution['name'] ?? 'TICH in Africa' }}</p>
        <p class="tich-doc-letterhead__tagline">{{ $institution['tagline'] ?? '' }}</p>
        @if (! empty($institution['address']))
            <p class="tich-doc-letterhead__address">{{ $institution['address'] }}</p>
        @endif
    </div>
</header>

@props(['title' => 'No internet connection', 'description' => 'You appear to be offline. Check your connection and try again.', 'inline' => false])

<div class="tich-state {{ $inline ? 'tich-state--inline' : '' }}">
    <div class="tich-state__icon tich-state__icon--offline">
        @include('partials.states.icons.wifi-off')
    </div>
    <h3 class="tich-state__title">{{ $title }}</h3>
    <p class="tich-state__description">{{ $description }}</p>
    <div class="tich-state__action">
        <button type="button" class="tich-btn tich-btn-secondary" onclick="window.location.reload()">Retry</button>
    </div>
</div>

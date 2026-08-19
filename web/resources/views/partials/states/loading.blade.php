@props(['title' => 'Loading', 'description' => 'Please wait while we fetch your data.', 'inline' => false])

<div class="tich-state {{ $inline ? 'tich-state--inline' : '' }}" data-state="loading">
    <div class="tich-state__icon tich-state__icon--loading">
        @include('partials.states.icons.loader')
    </div>
    <h3 class="tich-state__title">{{ $title }}</h3>
    @if ($description)
        <p class="tich-state__description">{{ $description }}</p>
    @endif
</div>

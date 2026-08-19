@props(['title' => 'No data yet', 'description' => null, 'icon' => 'inbox', 'action' => null, 'actionUrl' => null, 'actionLabel' => null, 'inline' => false])

<div class="tich-state {{ $inline ? 'tich-state--inline' : '' }}">
    <div class="tich-state__icon tich-state__icon--empty">
        @include('partials.states.icons.' . ($icon ?? 'inbox'))
    </div>
    <h3 class="tich-state__title">{{ $title }}</h3>
    @if ($description)
        <p class="tich-state__description">{{ $description }}</p>
    @endif
    @if ($actionUrl && $actionLabel)
        <div class="tich-state__action">
            <a href="{{ $actionUrl }}" class="tich-btn tich-btn-primary">{{ $actionLabel }}</a>
        </div>
    @endif
</div>

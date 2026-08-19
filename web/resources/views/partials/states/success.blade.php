@props(['title' => 'Success', 'description' => null, 'inline' => false, 'actionUrl' => null, 'actionLabel' => null])

<div class="tich-state {{ $inline ? 'tich-state--inline' : '' }}">
    <div class="tich-state__icon tich-state__icon--success">
        @include('partials.states.icons.check-circle')
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

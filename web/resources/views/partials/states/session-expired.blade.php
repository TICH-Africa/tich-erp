@props(['title' => 'Session expired', 'description' => 'Your session has timed out for security. Please sign in again to continue.', 'inline' => false])

<div class="tich-state {{ $inline ? 'tich-state--inline' : '' }}">
    <div class="tich-state__icon tich-state__icon--session">
        @include('partials.states.icons.clock-alert')
    </div>
    <h3 class="tich-state__title">{{ $title }}</h3>
    <p class="tich-state__description">{{ $description }}</p>
    <div class="tich-state__action">
        <a href="{{ route('login') }}" class="tich-btn tich-btn-primary">Sign in</a>
    </div>
</div>

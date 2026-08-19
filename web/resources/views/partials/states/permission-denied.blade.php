@props(['title' => 'Access denied', 'description' => 'You do not have permission to view this content. Contact your administrator if you believe this is an error.', 'inline' => false])

<div class="tich-state {{ $inline ? 'tich-state--inline' : '' }}">
    <div class="tich-state__icon tich-state__icon--permission">
        @include('partials.states.icons.shield-x')
    </div>
    <h3 class="tich-state__title">{{ $title }}</h3>
    <p class="tich-state__description">{{ $description }}</p>
    <div class="tich-state__action">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}" class="tich-btn tich-btn-secondary">Go back</a>
    </div>
</div>

@props(['title' => 'Something went wrong', 'description' => 'An unexpected error occurred. Please try again or contact support if the problem persists.', 'inline' => false, 'retryUrl' => null])

<div class="tich-state {{ $inline ? 'tich-state--inline' : '' }}">
    <div class="tich-state__icon tich-state__icon--error">
        @include('partials.states.icons.alert-circle')
    </div>
    <h3 class="tich-state__title">{{ $title }}</h3>
    <p class="tich-state__description">{{ $description }}</p>
    @if ($retryUrl)
        <div class="tich-state__action">
            <a href="{{ $retryUrl }}" class="tich-btn tich-btn-secondary">Try again</a>
        </div>
    @else
        <div class="tich-state__action">
            <button type="button" class="tich-btn tich-btn-secondary" onclick="window.location.reload()">Reload page</button>
        </div>
    @endif
</div>

@php
    $errorNav = app(\App\Services\ErrorNavigationService::class);
    $actions = $actions ?? $errorNav->actions();
    $hint = $hint ?? null;
@endphp

<section class="tich-section tich-error-page">
    <div class="tich-container">
        <div class="tich-error-page__card tich-card">
            <p class="tich-error-page__code">{{ $code }}</p>
            <h1 class="tich-h1 tich-error-page__title">{{ $title }}</h1>
            <p class="tich-text tich-error-page__message">{{ $message }}</p>

            @if ($hint)
                <p class="tich-caption tich-error-page__hint">{{ $hint }}</p>
            @endif

            <div class="tich-error-page__actions">
                @foreach ($actions as $action)
                    <a href="{{ $action['url'] }}"
                       @class([
                           'tich-btn',
                           'tich-btn-primary' => ! empty($action['primary']),
                           'tich-btn-secondary' => empty($action['primary']),
                       ])>{{ $action['label'] }}</a>
                @endforeach
                <button type="button" class="tich-btn tich-btn-secondary" onclick="if (window.history.length > 1) { history.back(); } else { window.location.href = @json($errorNav->homeUrl()); }">
                    Go back
                </button>
            </div>

            <p class="tich-caption tich-error-page__support">
                Need help? Return to the
                <a href="{{ route('home') }}" class="tich-link">homepage</a>
                @auth
                    or your <a href="{{ $errorNav->homeUrl() }}" class="tich-link">workspace</a>.
                @else
                    or <a href="{{ route('login') }}" class="tich-link">sign in</a> to continue.
                @endauth
            </p>
        </div>
    </div>
</section>

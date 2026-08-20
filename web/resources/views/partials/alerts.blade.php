@php
    $hasStatus = session('status') || session('success');
    $hasWarning = session('warning');
    $hasError = session('error');
    $hasValidationErrors = isset($errors) && $errors->any();
@endphp

@if ($hasStatus || $hasWarning || $hasError || $hasValidationErrors)
    <div class="tich-toast-stack" aria-live="polite" aria-atomic="true">
        @if ($hasStatus)
            <div class="tich-toast tich-toast--success" data-toast data-toast-autodismiss="5000" role="alert">
                <div class="tich-toast__content">
                    <p class="tich-toast__message">{{ session('status') ?? session('success') }}</p>
                </div>
                <button type="button" class="tich-toast__close" data-toast-dismiss aria-label="Dismiss notification">&times;</button>
            </div>
        @endif

        @if ($hasWarning)
            <div class="tich-toast tich-toast--warning" data-toast data-toast-autodismiss="8000" role="alert">
                <div class="tich-toast__content">
                    <p class="tich-toast__message">{{ session('warning') }}</p>
                </div>
                <button type="button" class="tich-toast__close" data-toast-dismiss aria-label="Dismiss notification">&times;</button>
            </div>
        @endif

        @if ($hasError)
            <div class="tich-toast tich-toast--error" data-toast data-toast-autodismiss="7000" role="alert">
                <div class="tich-toast__content">
                    <p class="tich-toast__message">{{ session('error') }}</p>
                </div>
                <button type="button" class="tich-toast__close" data-toast-dismiss aria-label="Dismiss notification">&times;</button>
            </div>
        @endif

        @if ($hasValidationErrors)
            <div class="tich-toast tich-toast--error" data-toast data-toast-autodismiss="10000" role="alert">
                <div class="tich-toast__content">
                    <p class="tich-toast__title">Please fix the following:</p>
                    <ul class="tich-toast__list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button type="button" class="tich-toast__close" data-toast-dismiss aria-label="Dismiss notification">&times;</button>
            </div>
        @endif
    </div>
@endif

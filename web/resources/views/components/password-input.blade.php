@props([
    'id',
    'name',
    'placeholder' => '',
    'autocomplete' => 'current-password',
    'required' => true,
    'hasError' => false,
])

<div class="tich-password-field">
    <input
        type="password"
        id="{{ $id }}"
        name="{{ $name }}"
        @if($required) required @endif
        autocomplete="{{ $autocomplete }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'tich-input tich-password-field__input' . ($hasError ? ' tich-input--error' : '')]) }}
    >
    <button
        type="button"
        class="tich-password-field__toggle"
        data-password-toggle
        aria-label="Show password"
        aria-pressed="false"
    >
        <svg class="tich-password-field__icon tich-password-field__icon--show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>
        <svg class="tich-password-field__icon tich-password-field__icon--hide" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
            <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
            <line x1="1" y1="1" x2="23" y2="23"/>
        </svg>
    </button>
</div>

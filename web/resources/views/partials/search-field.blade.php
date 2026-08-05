@php
    $action = $action ?? '';
    $placeholder = $placeholder ?? 'Search…';
    $value = $value ?? request('search');
    $buttonLabel = $buttonLabel ?? 'Search';
    $name = $name ?? 'search';
    $id = $id ?? $name;
@endphp

<label class="tich-search-field" for="{{ $id }}">
    <span class="tich-sr-only">{{ $buttonLabel }}</span>
    <input
        type="search"
        id="{{ $id }}"
        name="{{ $name }}"
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        class="tich-search-field__input"
    >
    <button type="submit" class="tich-search-field__btn">{{ $buttonLabel }}</button>
</label>

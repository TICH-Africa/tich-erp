@php
    $action = $action ?? '';
    $placeholder = $placeholder ?? 'Search…';
    $value = $value ?? request('search');
    $buttonLabel = $buttonLabel ?? 'Search';
    $name = $name ?? 'search';
    $id = $id ?? $name;
    $hideButton = $hideButton ?? false;
@endphp

<label @class(['tich-search-field', 'tich-search-field--live' => $hideButton]) for="{{ $id }}">
    <span class="tich-sr-only">{{ $hideButton ? $placeholder : $buttonLabel }}</span>
    <input
        type="search"
        id="{{ $id }}"
        name="{{ $name }}"
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        class="tich-search-field__input"
        autocomplete="off"
    >
    @unless ($hideButton)
        <button type="submit" class="tich-search-field__btn">{{ $buttonLabel }}</button>
    @endunless
</label>

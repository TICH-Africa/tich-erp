@props([
    'category' => null,
    'fieldIdPrefix' => '',
    'formContext' => 'create_category',
])

@php
    $useOld = old('_form') === $formContext;
    $categoryCode = $useOld ? old('category_code', '') : ($category->category_code ?? '');
    $categoryName = $useOld ? old('category_name', '') : ($category->category_name ?? '');
    $description = $useOld ? old('description', '') : ($category->description ?? '');
    $isActive = $useOld ? (bool) old('is_active', true) : ($category->is_active ?? true);
    $isCreate = $formContext === 'create_category';
@endphp

@if ($isCreate)
    <div class="tich-form-group">
        <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}category_code" @endif>Category code</label>
        <input
            type="text"
            name="category_code"
            @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}category_code" @endif
            class="tich-input"
            value="{{ $categoryCode }}"
            required
            placeholder="e.g. operations"
            pattern="[a-z][a-z0-9_-]*"
            title="Lowercase letters, numbers, hyphens, and underscores only"
        >
        <p class="tich-caption tich-mt-1">Lowercase identifier used internally. Cannot be changed after creation.</p>
    </div>
@else
    <div class="tich-form-group">
        <label class="tich-label">Category code</label>
        <input type="text" class="tich-input" value="{{ $categoryCode }}" readonly>
    </div>
@endif

<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}category_name" @endif>Display name</label>
    <input
        type="text"
        name="category_name"
        @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}category_name" @endif
        class="tich-input"
        value="{{ $categoryName }}"
        required
        placeholder="e.g. Operations"
    >
</div>

<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}description" @endif>Description</label>
    <textarea
        name="description"
        @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}description" @endif
        class="tich-input"
        rows="3"
    >{{ $description }}</textarea>
</div>

@if (! $isCreate)
    <label style="display: flex; gap: 0.5rem; align-items: center;">
        <input
            type="checkbox"
            name="is_active"
            value="1"
            @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}is_active" @endif
            @checked($isActive)
        >
        <span class="tich-text">Active</span>
    </label>
@endif

@props([
    'categories',
    'role' => null,
    'fieldIdPrefix' => '',
    'formContext' => 'create',
    'moduleOptions' => [],
    'selectedModuleKey' => '',
])

@php
    $useOld = old('_form') === $formContext;
    $roleName = $useOld ? old('role_name', '') : ($role->role_name ?? '');
    $displayName = $useOld ? old('display_name', '') : ($role->display_name ?? '');
    $roleCategory = $useOld ? old('role_category', '') : ($role->role_category ?? '');
    $description = $useOld ? old('description', '') : ($role->description ?? '');
@endphp

@if ($formContext === 'create' && $moduleOptions !== [])
    @include('admin.partials.role-module-field', [
        'moduleOptions' => $moduleOptions,
        'fieldIdPrefix' => $fieldIdPrefix,
        'selectedModuleKey' => $selectedModuleKey,
        'formContext' => $formContext,
    ])
@endif

<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}role_name" @endif>Role name</label>
    <input
        type="text"
        name="role_name"
        @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}role_name" @endif
        class="tich-input"
        value="{{ $roleName }}"
        @if ($role?->is_system_role) readonly @endif
        required
    >
    @if ($role?->is_system_role)
        <p class="tich-caption tich-mt-1">System role names are fixed.</p>
    @endif
</div>

<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}display_name" @endif>Display name</label>
    <input
        type="text"
        name="display_name"
        @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}display_name" @endif
        class="tich-input"
        value="{{ $displayName }}"
        required
    >
</div>

<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}role_category" @endif>Category</label>
    <select name="role_category" @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}role_category" @endif class="tich-input" required>
        <option value="">Select category…</option>
        @foreach ($categories as $value => $label)
            <option value="{{ $value }}" @selected($roleCategory === $value)>{{ $label }}</option>
        @endforeach
    </select>
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

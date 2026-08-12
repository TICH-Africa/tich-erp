@props([
    'moduleOptions',
    'fieldIdPrefix' => '',
    'selectedModuleKey' => '',
])

@php
    $useOld = old('_form') === ($formContext ?? 'create');
    $moduleKey = $useOld ? old('module_key', '') : ($selectedModuleKey ?? '');
@endphp

<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}module_key" @endif>Module</label>
    <select name="module_key" @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}module_key" @endif class="tich-input" required>
        <option value="">Select module…</option>
        @foreach ($moduleOptions as $key => $module)
            @if ($key === config('tich-module-roles.institution_module_key'))
                @continue
            @endif
            <option value="{{ $key }}" @selected($moduleKey === $key)>{{ $module['label'] }}</option>
        @endforeach
    </select>
    <p class="tich-caption tich-mt-1">Roles belong to one module. Permissions are scoped to that module's operations.</p>
</div>

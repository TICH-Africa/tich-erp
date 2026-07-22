@props([
    'parentCampuses',
    'campusTypes',
    'campus' => null,
    'fieldIdPrefix' => '',
    'excludeCampusId' => null,
])

<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}campus_code" @endif>Campus code</label>
    <input
        type="text"
        name="campus_code"
        @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}campus_code" @endif
        class="tich-input"
        value="{{ old('campus_code', $campus->campus_code ?? '') }}"
        required
    >
</div>
<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}campus_name" @endif>Campus name</label>
    <input
        type="text"
        name="campus_name"
        @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}campus_name" @endif
        class="tich-input"
        value="{{ old('campus_name', $campus->campus_name ?? '') }}"
        required
    >
</div>
<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}campus_type" @endif>Type</label>
    <select name="campus_type" @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}campus_type" @endif class="tich-input" required>
        @foreach ($campusTypes as $type)
            <option value="{{ $type }}" @selected(old('campus_type', $campus->campus_type ?? '') === $type)>
                {{ str_replace('_', ' ', ucfirst($type)) }}
            </option>
        @endforeach
    </select>
</div>
<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}parent_campus_id" @endif>Parent campus</label>
    <select name="parent_campus_id" @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}parent_campus_id" @endif class="tich-input">
        <option value="">None</option>
        @foreach ($parentCampuses as $parent)
            @if ($excludeCampusId && $parent->id == $excludeCampusId)
                @continue
            @endif
            <option value="{{ $parent->id }}" @selected(old('parent_campus_id', $campus->parent_campus_id ?? '') == $parent->id)>
                {{ $parent->campus_name }}
            </option>
        @endforeach
    </select>
</div>
<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}county" @endif>County</label>
    <input
        type="text"
        name="county"
        @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}county" @endif
        class="tich-input"
        value="{{ old('county', $campus->county ?? '') }}"
    >
</div>
<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}sub_county" @endif>Sub-county</label>
    <input
        type="text"
        name="sub_county"
        @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}sub_county" @endif
        class="tich-input"
        value="{{ old('sub_county', $campus->sub_county ?? '') }}"
    >
</div>
<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}physical_address" @endif>Physical address</label>
    <textarea name="physical_address" @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}physical_address" @endif class="tich-input" rows="2">{{ old('physical_address', $campus->physical_address ?? '') }}</textarea>
</div>
@if ($fieldIdPrefix)
    <label style="display: flex; gap: 0.5rem; align-items: center;">
        <input
            type="checkbox"
            name="is_active"
            value="1"
            id="{{ $fieldIdPrefix }}is_active"
            @checked(old('is_active', $campus->is_active ?? true))
        >
        <span class="tich-text">Active</span>
    </label>
@endif

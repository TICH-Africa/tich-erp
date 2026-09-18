@props([
    'parentCampuses',
    'campusTypes',
    'campus' => null,
    'fieldIdPrefix' => '',
    'excludeCampusId' => null,
])

<div class="uf-form-grid-2">
    <div class="uf-field">
        <label @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}campus_code" @endif>Campus code</label>
        <input
            type="text"
            name="campus_code"
            @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}campus_code" @endif
            value="{{ old('campus_code', $campus->campus_code ?? '') }}"
            required
        >
    </div>
    <div class="uf-field">
        <label @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}campus_name" @endif>Campus name</label>
        <input
            type="text"
            name="campus_name"
            @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}campus_name" @endif
            value="{{ old('campus_name', $campus->campus_name ?? '') }}"
            required
        >
    </div>
    <div class="uf-field">
        <label @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}campus_type" @endif>Type</label>
        <select name="campus_type" @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}campus_type" @endif required>
            @foreach ($campusTypes as $value => $label)
                <option value="{{ $value }}" @selected(old('campus_type', $campus->campus_type ?? '') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="uf-field">
        <label @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}parent_campus_id" @endif>Parent campus</label>
        <select name="parent_campus_id" @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}parent_campus_id" @endif>
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
    <div class="uf-field">
        <label @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}county" @endif>County</label>
        <input
            type="text"
            name="county"
            @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}county" @endif
            value="{{ old('county', $campus->county ?? '') }}"
        >
    </div>
    <div class="uf-field">
        <label @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}sub_county" @endif>Sub-county</label>
        <input
            type="text"
            name="sub_county"
            @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}sub_county" @endif
            value="{{ old('sub_county', $campus->sub_county ?? '') }}"
        >
    </div>
    <div class="uf-field" style="grid-column: 1 / -1;">
        <label @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}physical_address" @endif>Physical address</label>
        <textarea name="physical_address" @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}physical_address" @endif rows="2">{{ old('physical_address', $campus->physical_address ?? '') }}</textarea>
    </div>
    @if ($fieldIdPrefix)
        <div class="uf-field" style="flex-direction: row; align-items: center; gap: 0.5rem;">
            <input
                type="checkbox"
                name="is_active"
                value="1"
                id="{{ $fieldIdPrefix }}is_active"
                @checked(old('is_active', $campus->is_active ?? true))
            >
            <label for="{{ $fieldIdPrefix }}is_active" style="font-weight: 400;">Active</label>
        </div>
    @endif
</div>

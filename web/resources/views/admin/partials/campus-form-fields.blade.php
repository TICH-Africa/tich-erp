@props([
    'parentCampuses',
    'campusTypes',
    'campus' => null,
    'fieldIdPrefix' => '',
    'excludeCampusId' => null,
    'counties' => config('tich-application.counties', []),
])

@php
    $selectedType = old('campus_type', $campus->campus_type ?? 'main');
    $selectedCounty = old('county', $campus->county ?? '');
    $countyOptions = collect($counties)
        ->push($selectedCounty)
        ->filter()
        ->unique()
        ->sort()
        ->values();
@endphp

<div class="uf-form-grid-2" data-campus-form>
    <div class="uf-field" data-campus-name-field>
        <label
            @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}campus_name" @endif
            data-campus-name-label
        >@if ($selectedType === 'community_college') Site @else Campus name @endif</label>
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
        <select
            name="campus_type"
            @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}campus_type" @endif
            data-campus-type-select
            required
        >
            @foreach ($campusTypes as $value => $label)
                <option value="{{ $value }}" @selected(old('campus_type', $campus->campus_type ?? '') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>
    <div
        class="uf-field"
        data-campus-standard-field
        @if ($selectedType === 'community_college') hidden @endif
    >
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
        <select
            name="county"
            @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}county" @endif
            @if ($selectedType === 'community_college') required @endif
        >
            <option value="">Select county</option>
            @foreach ($countyOptions as $county)
                <option value="{{ $county }}" @selected(old('county', $campus->county ?? '') == $county)>
                    {{ $county }}
                </option>
            @endforeach
        </select>
    </div>
    <div
        class="uf-field"
        data-campus-standard-field
        @if ($selectedType === 'community_college') hidden @endif
    >
        <label @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}sub_county" @endif>Sub-county</label>
        <input
            type="text"
            name="sub_county"
            @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}sub_county" @endif
            value="{{ old('sub_county', $campus->sub_county ?? '') }}"
        >
    </div>
    <div
        class="uf-field"
        style="grid-column: 1 / -1;"
        data-campus-standard-field
        @if ($selectedType === 'community_college') hidden @endif
    >
        <label @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}physical_address" @endif>Physical address</label>
        <textarea
            name="physical_address"
            @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}physical_address" @endif
            rows="2"
        >{{ old('physical_address', $campus->physical_address ?? '') }}</textarea>
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

<script>
(function () {
    document.querySelectorAll('[data-campus-form]').forEach(function (formFields) {
        var typeSelect = formFields.querySelector('[data-campus-type-select]');
        if (!typeSelect) {
            return;
        }

        var prefix = typeSelect.id.replace(/campus_type$/, '');
        var nameLabel = formFields.querySelector('[data-campus-name-label]');
        var countyInput = document.getElementById(prefix + 'county');
        var parentInput = document.getElementById(prefix + 'parent_campus_id');
        var subCountyInput = document.getElementById(prefix + 'sub_county');
        var addressInput = document.getElementById(prefix + 'physical_address');
        var standardFields = formFields.querySelectorAll('[data-campus-standard-field]');

        function syncCampusFields() {
            var isCommunityCollege = typeSelect.value === 'community_college';

            standardFields.forEach(function (field) {
                field.hidden = isCommunityCollege;
            });

            if (nameLabel) {
                nameLabel.textContent = isCommunityCollege ? 'Site' : 'Campus name';
            }

            if (countyInput) {
                countyInput.required = isCommunityCollege;
            }

            if (isCommunityCollege) {
                if (parentInput) {
                    parentInput.value = '';
                }
                if (subCountyInput) {
                    subCountyInput.value = '';
                }
                if (addressInput) {
                    addressInput.value = '';
                }
            }
        }

        typeSelect.addEventListener('change', syncCampusFields);
        syncCampusFields();
    });
})();
</script>

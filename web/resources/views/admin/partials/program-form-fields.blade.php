@props([
    'learningDepartments',
    'programTypes',
    'programStatuses',
    'program' => null,
    'fieldIdPrefix' => '',
])

<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}cover_image" @endif>Cover image</label>
    @if ($program?->coverImageUrl())
        <img
            src="{{ $program->coverImageUrl() }}"
            alt="{{ $program->program_name }}"
            id="{{ $fieldIdPrefix }}cover_preview"
            class="tich-program-form-cover-preview"
            style="display: block; width: 100%; max-width: 16rem; height: 8rem; object-fit: cover; border-radius: var(--radius-md, 0.5rem); margin-bottom: 0.75rem;"
        >
    @else
        <img
            src=""
            alt=""
            id="{{ $fieldIdPrefix }}cover_preview"
            class="tich-program-form-cover-preview"
            hidden
            style="width: 100%; max-width: 16rem; height: 8rem; object-fit: cover; border-radius: var(--radius-md, 0.5rem); margin-bottom: 0.75rem;"
        >
    @endif
    <input
        type="file"
        name="cover_image"
        @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}cover_image" @endif
        class="tich-input"
        accept="image/jpeg,image/png,image/webp,image/gif"
        @if (! $program) required @endif
    >
    <p class="tich-caption tich-mt-2">Upload a file only (JPG, PNG, GIF, or WebP) — not a link. Compressed and saved as WebP. Shown on the programmes catalogue and homepage.</p>
</div>
<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}program_code" @endif>Programme code</label>
    <input
        type="text"
        name="program_code"
        @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}program_code" @endif
        class="tich-input"
        value="{{ old('program_code', $program->program_code ?? '') }}"
        required
        placeholder="e.g. CHP"
    >
</div>
<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}program_name" @endif>Programme name</label>
    <input
        type="text"
        name="program_name"
        @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}program_name" @endif
        class="tich-input"
        value="{{ old('program_name', $program->program_name ?? '') }}"
        required
    >
</div>
<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}department_id" @endif>Academic department</label>
    <select name="department_id" @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}department_id" @endif class="tich-input" required>
        <option value="">Select department…</option>
        @foreach ($learningDepartments as $department)
            <option value="{{ $department->id }}" @selected(old('department_id', $program->department_id ?? '') == $department->id)>
                {{ $department->dept_name }} ({{ $department->dept_code }})
            </option>
        @endforeach
    </select>
</div>
<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}program_type" @endif>Type</label>
    <select name="program_type" @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}program_type" @endif class="tich-input" required>
        @foreach ($programTypes as $type)
            <option value="{{ $type }}" @selected(old('program_type', $program->program_type ?? '') === $type)>
                {{ ucfirst(str_replace('_', ' ', $type)) }}
            </option>
        @endforeach
    </select>
</div>
<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}regulatory_body" @endif>Regulatory body</label>
    <input
        type="text"
        name="regulatory_body"
        @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}regulatory_body" @endif
        class="tich-input"
        value="{{ old('regulatory_body', $program->regulatory_body ?? '') }}"
        placeholder="NITA, CDACC, TVET…"
    >
</div>
<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}duration_months" @endif>Duration (months)</label>
    <input
        type="number"
        name="duration_months"
        @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}duration_months" @endif
        class="tich-input"
        value="{{ old('duration_months', $program->duration_months ?? 12) }}"
        min="1"
    >
</div>
<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}status" @endif>Status</label>
    <select name="status" @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}status" @endif class="tich-input" required>
        @foreach ($programStatuses as $status)
            <option value="{{ $status }}" @selected(old('status', $program->status ?? 'active') === $status)>
                {{ ucfirst(str_replace('_', ' ', $status)) }}
            </option>
        @endforeach
    </select>
</div>
<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}homepage_tagline" @endif>Homepage tagline</label>
    <textarea name="homepage_tagline" @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}homepage_tagline" @endif class="tich-input" rows="3">{{ old('homepage_tagline', $program->homepage_tagline ?? '') }}</textarea>
</div>
<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}entry_requirements" @endif>Entry requirements</label>
    <textarea name="entry_requirements" @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}entry_requirements" @endif class="tich-input" rows="2">{{ old('entry_requirements', $program->entry_requirements ?? '') }}</textarea>
</div>
<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}homepage_display_order" @endif>Homepage display order</label>
    <input
        type="number"
        name="homepage_display_order"
        @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}homepage_display_order" @endif
        class="tich-input"
        value="{{ old('homepage_display_order', $program->homepage_display_order ?? 0) }}"
        min="0"
    >
</div>
<label style="display: flex; gap: 0.5rem; align-items: center;">
    <input
        type="checkbox"
        name="is_featured_on_homepage"
        value="1"
        @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}is_featured_on_homepage" @endif
        @checked(old('is_featured_on_homepage', $program->is_featured_on_homepage ?? false))
    >
    <span class="tich-text">Featured on homepage</span>
</label>

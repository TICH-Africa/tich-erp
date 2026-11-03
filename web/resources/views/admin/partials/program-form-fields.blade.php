@props([
    'learningDepartments',
    'programTypes',
    'programStatuses',
    'program' => null,
    'fieldIdPrefix' => '',
    'requireCoverImage' => null,
])

@php
    $requireCoverImage = $requireCoverImage ?? ! $program;

    $programFormScope = $programFormScope ?? null;
    if ($programFormScope === null) {
        $prefix = (string) ($fieldIdPrefix ?? '');
        $programFormScope = str_starts_with($prefix, 'program-create-') || str_starts_with($prefix, 'create_')
            ? 'create'
            : 'edit';
    }

    $failedFormScope = old('_form');
    $useFlashedInput = $failedFormScope === null || $failedFormScope === $programFormScope;

    $pfOld = static function (string $key, mixed $default = '') use ($useFlashedInput): mixed {
        return $useFlashedInput ? old($key, $default) : $default;
    };

    $pfOldChecked = static function (string $key, mixed $default = false) use ($useFlashedInput): bool {
        if ($useFlashedInput && old($key) !== null) {
            return filter_var(old($key), FILTER_VALIDATE_BOOLEAN);
        }

        return (bool) $default;
    };

    $coverDraftService = app(\App\Services\ProgramFormCoverDraftService::class);
    $coverDraftToken = $useFlashedInput
        ? (string) $pfOld('cover_image_draft', $coverDraftService->currentToken($programFormScope) ?? '')
        : '';
    $coverDraftPreviewUrl = ($useFlashedInput && $coverDraftToken !== '')
        ? route('admin.programs.form-cover-draft', ['scope' => $programFormScope, 'token' => $coverDraftToken])
        : null;
    $coverPreviewUrl = $coverDraftPreviewUrl ?: ($program?->coverImageUrl());
    $coverFileRequired = $requireCoverImage && ! $coverDraftPreviewUrl;
@endphp

<p class="tich-caption tich-mb-4"><x-required-mark /> Required fields</p>

<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}cover_image" @endif>
        Cover image @if ($requireCoverImage)<x-required-mark />@endif
    </label>
    <input type="hidden" name="cover_image_draft" value="{{ $coverDraftToken }}">
    <img
        src="{{ $coverPreviewUrl ?? '' }}"
        alt="{{ $program->program_name ?? 'Cover preview' }}"
        id="{{ $fieldIdPrefix }}cover_preview"
        class="tich-program-form-cover-preview"
        @if (! $coverPreviewUrl) hidden @endif
        style="display: {{ $coverPreviewUrl ? 'block' : 'none' }}; width: 100%; max-width: 16rem; height: 8rem; object-fit: cover; border-radius: var(--radius-md, 0.5rem); margin-bottom: 0.75rem;"
    >
    <input
        type="file"
        name="cover_image"
        @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}cover_image" @endif
        class="tich-input"
        accept="image/jpeg,image/png,image/webp,image/gif"
        @if ($coverFileRequired) required @endif
    >
    @error('cover_image')
        <p class="tich-field-error tich-mt-2">{{ $message }}</p>
    @enderror
    <p class="tich-caption tich-mt-2">
        @if ($coverDraftPreviewUrl)
            Your uploaded cover is kept while you fix other fields. Upload again only if you want to replace it.
        @elseif ($requireCoverImage)
            Upload a file (JPG, PNG, GIF, or WebP) - not a link. Compressed and saved as WebP. Shown on the programmes catalogue and homepage.
        @else
            Optional - leave empty to keep the current cover image. JPG, PNG, GIF, or WebP; compressed and saved as WebP when uploaded.
        @endif
    </p>
</div>
<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}program_code" @endif>Programme code <x-required-mark /></label>
    <input
        type="text"
        name="program_code"
        @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}program_code" @endif
        class="tich-input"
        value="{{ $pfOld('program_code', $program->program_code ?? '') }}"
        required
        placeholder="e.g. CHP"
    >
</div>
<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}program_name" @endif>Programme name <x-required-mark /></label>
    <input
        type="text"
        name="program_name"
        @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}program_name" @endif
        class="tich-input"
        value="{{ $pfOld('program_name', $program->program_name ?? '') }}"
        required
    >
</div>
<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}department_id" @endif>Academic department <x-required-mark /></label>
    <select name="department_id" @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}department_id" @endif class="tich-input" required>
        <option value="">Select department…</option>
        @foreach ($learningDepartments as $department)
            <option value="{{ $department->id }}" @selected($pfOld('department_id', $program->department_id ?? '') == $department->id)>
                {{ $department->dept_name }} ({{ $department->dept_code }})
            </option>
        @endforeach
    </select>
</div>
<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}program_type" @endif>Type <x-required-mark /></label>
    <select name="program_type" @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}program_type" @endif class="tich-input" required>
        @foreach ($programTypes as $type)
            <option value="{{ $type }}" @selected($pfOld('program_type', $program->program_type ?? '') === $type)>
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
        value="{{ $pfOld('regulatory_body', $program->regulatory_body ?? '') }}"
        placeholder="NITA, CDACC, TVET…"
    >
</div>
@include('admin.partials.program-tier-fields', ['program' => $program, 'fieldIdPrefix' => $fieldIdPrefix])

<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}duration_months" @endif>Duration (months)</label>
    <input
        type="number"
        name="duration_months"
        @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}duration_months" @endif
        class="tich-input"
        value="{{ $pfOld('duration_months', $program->duration_months ?? 12) }}"
        min="1"
    >
</div>
<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}status" @endif>Status <x-required-mark /></label>
    <select name="status" @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}status" @endif class="tich-input" required>
        @foreach ($programStatuses as $status)
            <option value="{{ $status }}" @selected($pfOld('status', $program->status ?? 'active') === $status)>
                {{ ucfirst(str_replace('_', ' ', $status)) }}
            </option>
        @endforeach
    </select>
</div>
<div class="tich-form-group">
    <x-cms-basic-editor
        name="homepage_tagline"
        :id="($fieldIdPrefix ?? '').'homepage_tagline'"
        label="Course body / homepage tagline"
        :value="$pfOld('homepage_tagline', $program->homepage_tagline ?? '')"
        :use-form-old="$useFlashedInput"
        min-height="8rem"
    />
</div>
<div class="tich-form-group">
    <x-cms-basic-editor
        name="entry_requirements"
        :id="($fieldIdPrefix ?? '').'entry_requirements'"
        label="Entry requirements"
        :value="$pfOld('entry_requirements', $program->entry_requirements ?? '')"
        :use-form-old="$useFlashedInput"
        min-height="6rem"
    />
</div>
<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}homepage_display_order" @endif>Homepage display order</label>
    <input
        type="number"
        name="homepage_display_order"
        @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}homepage_display_order" @endif
        class="tich-input"
        value="{{ $pfOld('homepage_display_order', $program->homepage_display_order ?? 0) }}"
        min="0"
    >
</div>
<label style="display: flex; gap: 0.5rem; align-items: center;">
    <input
        type="checkbox"
        name="is_featured_on_homepage"
        value="1"
        @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}is_featured_on_homepage" @endif
        @checked($pfOldChecked('is_featured_on_homepage', $program->is_featured_on_homepage ?? false))
    >
    <span class="tich-text">Featured on homepage</span>
</label>

<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}total_semester_fee" @endif>Total semester fee (KES)</label>
    <input
        type="number"
        name="total_semester_fee"
        @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}total_semester_fee" @endif
        class="tich-input"
        value="{{ $pfOld('total_semester_fee', $program->feeStructure?->total_semester_fee ?? '') }}"
        min="0"
        step="0.01"
        placeholder="Enter total fee per semester"
    >
    <p class="tich-caption tich-mt-2">Optional: Enter the total fee per semester. This will create/update the fee structure for the current academic year.</p>
</div>

@php($feeStructure = $feeStructure ?? null)
@php($defaults = config('finance.fee_defaults'))

<div class="tich-form-row">
    <label class="tich-label" for="program_id">Programme</label>
    <select id="program_id" name="program_id" class="tich-input" required>
        @foreach ($programs as $program)
            <option value="{{ $program->id }}" @selected(old('program_id', $feeStructure?->program_id) == $program->id)>{{ $program->program_name }}</option>
        @endforeach
    </select>
</div>
<div class="tich-form-row">
    <label class="tich-label" for="academic_year_id">Academic year</label>
    <select id="academic_year_id" name="academic_year_id" class="tich-input" required>
        @foreach ($academicYears as $year)
            <option value="{{ $year->id }}" @selected(old('academic_year_id', $feeStructure?->academic_year_id) == $year->id)>{{ $year->year_label }}</option>
        @endforeach
    </select>
</div>
<div class="tich-form-row">
    <label class="tich-label" for="effective_from">Effective from</label>
    <input type="date" id="effective_from" name="effective_from" class="tich-input" value="{{ old('effective_from', optional($feeStructure?->effective_from)->format('Y-m-d') ?? now()->toDateString()) }}" required>
</div>

<h2 class="tich-h3 tich-mt-6">Application fee</h2>
<p class="tich-caption tich-mb-4">Paid once after application is approved.</p>
<div class="tich-form-row">
    <label class="tich-label" for="application_fee">Application fee (KES)</label>
    <input type="number" step="0.01" min="0" id="application_fee" name="application_fee" class="tich-input" value="{{ old('application_fee', $feeStructure?->application_fee ?? $defaults['application_fee']) }}" required>
</div>

<h2 class="tich-h3 tich-mt-6">Semester charges</h2>
<p class="tich-caption tich-mb-4">Applied each semester. Transport and accommodation are optional add-ons.</p>
@foreach (\App\Models\FeeStructure::SEMESTER_CHARGES as $field => $label)
    <div class="tich-form-row">
        <label class="tich-label" for="{{ $field }}">{{ $label }}</label>
        <input type="number" step="0.01" min="0" id="{{ $field }}" name="{{ $field }}" class="tich-input" value="{{ old($field, $feeStructure?->{$field} ?? 0) }}" @if($field === 'tuition_fee') required @endif>
    </div>
@endforeach
<div class="tich-form-row">
    <label class="tich-label" for="transport_fee">Transport (optional, per booklet)</label>
    <input type="number" step="0.01" min="0" id="transport_fee" name="transport_fee" class="tich-input" value="{{ old('transport_fee', $feeStructure?->transport_fee ?? 0) }}">
</div>
<div class="tich-form-row">
    <label class="tich-label" for="accommodation_fee">Accommodation (optional)</label>
    <input type="number" step="0.01" min="0" id="accommodation_fee" name="accommodation_fee" class="tich-input" value="{{ old('accommodation_fee', $feeStructure?->accommodation_fee ?? 0) }}">
</div>

<h2 class="tich-h3 tich-mt-6">Yearly charges</h2>
<div class="tich-form-row">
    <label class="tich-label" for="qa_annual_fee">Quality assurance fee (annual)</label>
    <input type="number" step="0.01" min="0" id="qa_annual_fee" name="qa_annual_fee" class="tich-input" value="{{ old('qa_annual_fee', $feeStructure?->qa_annual_fee ?? $defaults['qa_annual_fee']) }}" required>
</div>

<h2 class="tich-h3 tich-mt-6">Paid once throughout the programme</h2>
<div class="tich-form-row">
    <label class="tich-label">
        <input type="checkbox" name="requires_indexing_nck" value="1" @checked(old('requires_indexing_nck', $feeStructure?->requires_indexing_nck))>
        Requires indexing (NCK) - Kenya registered community health nursing only
    </label>
</div>
<div class="tich-form-row">
    <label class="tich-label" for="indexing_nck_fee">Indexing (NCK) fee</label>
    <input type="number" step="0.01" min="0" id="indexing_nck_fee" name="indexing_nck_fee" class="tich-input" value="{{ old('indexing_nck_fee', $feeStructure?->indexing_nck_fee) }}">
</div>

<h2 class="tich-h3 tich-mt-6">Post learning</h2>
<div class="tich-form-row">
    <label class="tich-label" for="graduation_fee">Graduation fees</label>
    <input type="number" step="0.01" min="0" id="graduation_fee" name="graduation_fee" class="tich-input" value="{{ old('graduation_fee', $feeStructure?->graduation_fee ?? $defaults['graduation_fee']) }}" required>
</div>

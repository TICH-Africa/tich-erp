@php($feeStructure = $feeStructure ?? null)
@php($defaults = config('finance.fee_defaults'))

<div class="uf-form-section">
    <div class="uf-section-head">Programme &amp; Effective Date</div>
    <div class="uf-section-body">
        <div class="uf-form-grid-2">
            <div class="uf-field">
                <label for="program_id">Programme <span class="uf-req">*</span></label>
                <select
                    id="program_id"
                    name="program_id"
                    required
                    class="{{ $errors->has('program_id') ? 'is-invalid' : '' }}"
                >
                    @foreach ($programs as $program)
                        <option value="{{ $program->id }}" @selected(old('program_id', $feeStructure?->program_id) == $program->id)>{{ $program->program_name }}</option>
                    @endforeach
                </select>
                @error('program_id')
                    <span class="uf-error">{{ $message }}</span>
                @enderror
            </div>
            <div class="uf-field">
                <label for="academic_year_id">Academic year <span class="uf-req">*</span></label>
                <select
                    id="academic_year_id"
                    name="academic_year_id"
                    required
                    class="{{ $errors->has('academic_year_id') ? 'is-invalid' : '' }}"
                >
                    @foreach ($academicYears as $year)
                        <option value="{{ $year->id }}" @selected(old('academic_year_id', $feeStructure?->academic_year_id) == $year->id)>{{ $year->year_label }}</option>
                    @endforeach
                </select>
                @error('academic_year_id')
                    <span class="uf-error">{{ $message }}</span>
                @enderror
            </div>
            <div class="uf-field">
                <label for="effective_from">Effective from <span class="uf-req">*</span></label>
                <input
                    type="date"
                    id="effective_from"
                    name="effective_from"
                    value="{{ old('effective_from', optional($feeStructure?->effective_from)->format('Y-m-d') ?? now()->toDateString()) }}"
                    required
                    class="{{ $errors->has('effective_from') ? 'is-invalid' : '' }}"
                >
                @error('effective_from')
                    <span class="uf-error">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>
</div>

<div class="uf-form-section">
    <div class="uf-section-head">Application Fee</div>
    <div class="uf-section-body">
        <p class="uf-hint">Paid once after application is approved.</p>
        <div class="uf-field">
            <label for="application_fee">Application fee (KES) <span class="uf-req">*</span></label>
            <input
                type="number"
                step="0.01"
                min="0"
                id="application_fee"
                name="application_fee"
                value="{{ old('application_fee', $feeStructure?->application_fee ?? $defaults['application_fee']) }}"
                required
                class="{{ $errors->has('application_fee') ? 'is-invalid' : '' }}"
            >
            @error('application_fee')
                <span class="uf-error">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="uf-form-section">
    <div class="uf-section-head">Semester Charges</div>
    <div class="uf-section-body">
        <p class="uf-hint">Applied each semester. Transport and accommodation are optional add-ons.</p>
        <div class="uf-form-grid-2">
            @foreach (\App\Models\FeeStructure::SEMESTER_CHARGES as $field => $label)
                <div class="uf-field">
                    <label for="{{ $field }}">{{ $label }} @if($field === 'tuition_fee')<span class="uf-req">*</span>@endif</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        id="{{ $field }}"
                        name="{{ $field }}"
                        value="{{ old($field, $feeStructure?->{$field} ?? 0) }}"
                        @if($field === 'tuition_fee') required @endif
                        class="{{ $errors->has($field) ? 'is-invalid' : '' }}"
                    >
                    @error($field)
                        <span class="uf-error">{{ $message }}</span>
                    @enderror
                </div>
            @endforeach
            <div class="uf-field">
                <label for="transport_fee">Transport (optional, per booklet)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    id="transport_fee"
                    name="transport_fee"
                    value="{{ old('transport_fee', $feeStructure?->transport_fee ?? 0) }}"
                    class="{{ $errors->has('transport_fee') ? 'is-invalid' : '' }}"
                >
                @error('transport_fee')
                    <span class="uf-error">{{ $message }}</span>
                @enderror
            </div>
            <div class="uf-field">
                <label for="accommodation_fee">Accommodation (optional)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    id="accommodation_fee"
                    name="accommodation_fee"
                    value="{{ old('accommodation_fee', $feeStructure?->accommodation_fee ?? 0) }}"
                    class="{{ $errors->has('accommodation_fee') ? 'is-invalid' : '' }}"
                >
                @error('accommodation_fee')
                    <span class="uf-error">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>
</div>

<div class="uf-form-section">
    <div class="uf-section-head">Yearly Charges</div>
    <div class="uf-section-body">
        <div class="uf-field">
            <label for="qa_annual_fee">Quality assurance fee (annual) <span class="uf-req">*</span></label>
            <input
                type="number"
                step="0.01"
                min="0"
                id="qa_annual_fee"
                name="qa_annual_fee"
                value="{{ old('qa_annual_fee', $feeStructure?->qa_annual_fee ?? $defaults['qa_annual_fee']) }}"
                required
                class="{{ $errors->has('qa_annual_fee') ? 'is-invalid' : '' }}"
            >
            @error('qa_annual_fee')
                <span class="uf-error">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="uf-form-section">
    <div class="uf-section-head">Paid Once Throughout the Programme</div>
    <div class="uf-section-body">
        <div class="uf-field">
            <label>
                <input type="checkbox" name="requires_indexing_nck" value="1" @checked(old('requires_indexing_nck', $feeStructure?->requires_indexing_nck))>
                Requires indexing (NCK) - Kenya registered community health nursing only
            </label>
        </div>
        <div class="uf-field">
            <label for="indexing_nck_fee">Indexing (NCK) fee</label>
            <input
                type="number"
                step="0.01"
                min="0"
                id="indexing_nck_fee"
                name="indexing_nck_fee"
                value="{{ old('indexing_nck_fee', $feeStructure?->indexing_nck_fee) }}"
                class="{{ $errors->has('indexing_nck_fee') ? 'is-invalid' : '' }}"
            >
            @error('indexing_nck_fee')
                <span class="uf-error">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="uf-form-section">
    <div class="uf-section-head">Post Learning</div>
    <div class="uf-section-body">
        <div class="uf-field">
            <label for="graduation_fee">Graduation fees <span class="uf-req">*</span></label>
            <input
                type="number"
                step="0.01"
                min="0"
                id="graduation_fee"
                name="graduation_fee"
                value="{{ old('graduation_fee', $feeStructure?->graduation_fee ?? $defaults['graduation_fee']) }}"
                required
                class="{{ $errors->has('graduation_fee') ? 'is-invalid' : '' }}"
            >
            @error('graduation_fee')
                <span class="uf-error">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

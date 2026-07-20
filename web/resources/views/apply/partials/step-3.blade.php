<h2 class="tich-h3">Step 3 — Academic qualifications</h2>
<p class="tich-text tich-mt-2">Provide your highest entry qualification for the programme you selected.</p>

<div class="tich-form-group tich-mt-6">
    <label for="entry_qualification" class="tich-label">Entry qualification</label>
    <select id="entry_qualification" name="entry_qualification" class="tich-input" required>
        <option value="">Select qualification</option>
        @foreach ($entryQualifications as $value => $label)
            <option value="{{ $value }}" @selected(old('entry_qualification', $draft['entry_qualification'] ?? '') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @error('entry_qualification')<p class="tich-field-error">{{ $message }}</p>@enderror
</div>

<div class="tich-grid tich-grid--2" style="gap: 1rem;">
    <div class="tich-form-group">
        <label for="kcse_grade" class="tich-label">KCSE mean grade</label>
        <input type="text" id="kcse_grade" name="kcse_grade" class="tich-input" placeholder="e.g. C-" value="{{ old('kcse_grade', $draft['kcse_grade'] ?? '') }}">
    </div>
    <div class="tich-form-group">
        <label for="kcse_year" class="tich-label">KCSE year</label>
        <input type="number" id="kcse_year" name="kcse_year" class="tich-input" min="1990" max="{{ date('Y') }}" value="{{ old('kcse_year', $draft['kcse_year'] ?? '') }}">
    </div>
</div>

<div class="tich-form-group">
    <label for="previous_institution" class="tich-label">Previous institution (if any)</label>
    <input type="text" id="previous_institution" name="previous_institution" class="tich-input" value="{{ old('previous_institution', $draft['previous_institution'] ?? '') }}">
</div>

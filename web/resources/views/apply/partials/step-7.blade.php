<h2 class="tich-h3">Step 7 — Review &amp; submit</h2>
<p class="tich-text tich-mt-2">Confirm your details before sending the application to the academic department.</p>

@if ($review)
    <dl class="tich-review-list tich-mt-6">
        <div><dt>Programme</dt><dd>{{ $review['program']->program_name ?? '—' }} ({{ $review['program']->program_code ?? '—' }})</dd></div>
        <div><dt>Preferred campus</dt><dd>{{ $review['campus']->campus_name ?? 'No preference' }}</dd></div>
        <div><dt>Full name</dt><dd>{{ trim(($review['data']['first_name'] ?? '').' '.($review['data']['middle_name'] ?? '').' '.($review['data']['surname'] ?? '')) }}</dd></div>
        <div><dt>Email</dt><dd>{{ $review['data']['email'] ?? '—' }}</dd></div>
        <div><dt>Phone</dt><dd>{{ $review['data']['phone_number'] ?? '—' }}</dd></div>
        <div><dt>Entry qualification</dt><dd>{{ $entryQualifications[$review['data']['entry_qualification'] ?? ''] ?? '—' }}</dd></div>
        @if (!empty($review['data']['kcse_grade']))
            <div><dt>KCSE grade</dt><dd>{{ $review['data']['kcse_grade'] }} @if(!empty($review['data']['kcse_year'])) ({{ $review['data']['kcse_year'] }}) @endif</dd></div>
        @endif
        <div><dt>Sponsorship</dt><dd>{{ $sponsorshipOptions[$review['data']['sponsorship_type'] ?? ''] ?? '—' }}</dd></div>
        @if(!empty($review['data']['sponsor_organization']))
            <div><dt>Sponsor organization</dt><dd>{{ $review['data']['sponsor_organization'] }}</dd></div>
        @endif
        <div><dt>Next of kin</dt><dd>{{ $review['data']['next_of_kin_name'] ?? '—' }} ({{ $relationshipOptions[$review['data']['next_of_kin_relationship'] ?? ''] ?? '—' }})</dd></div>
    </dl>
@endif

<div class="tich-form-group tich-mt-6">
    <label style="display:flex;gap:0.5rem;align-items:flex-start;">
        <input type="checkbox" name="confirm_accuracy" value="1" @checked(old('confirm_accuracy')) required>
        <span class="tich-text">I confirm that the information provided is accurate and complete.</span>
    </label>
    @error('confirm_accuracy')<p class="tich-field-error">{{ $message }}</p>@enderror
</div>

<div class="tich-form-group">
    <label style="display:flex;gap:0.5rem;align-items:flex-start;">
        <input type="checkbox" name="confirm_terms" value="1" @checked(old('confirm_terms')) required>
        <span class="tich-text">I agree that TICH may contact me about this application and store my data for admissions processing.</span>
    </label>
    @error('confirm_terms')<p class="tich-field-error">{{ $message }}</p>@enderror
</div>

<p class="tich-caption tich-mt-4">After submission you will receive an application number. Academic staff will review your application — the department dashboard is coming next.</p>
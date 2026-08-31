@php
    $reviewData = $review['data'] ?? [];
    $genderLabels = ['male' => 'Male', 'female' => 'Female', 'other' => 'Other', 'prefer_not_to_say' => 'Prefer not to say'];
    $fullName = trim(implode(' ', array_filter([
        $reviewData['first_name'] ?? '',
        $reviewData['middle_name'] ?? '',
        $reviewData['surname'] ?? '',
    ])));
    $documents = $reviewData['documents'] ?? [];
@endphp

<h2 class="tich-h3">Step 7 - Review &amp; submit</h2>
<p class="tich-text tich-mt-2">Confirm your details before sending the application to the academic department.</p>

@if ($review)
    <section class="tich-mt-6">
        <h3 class="tich-h4">Programme</h3>
        <dl class="tich-review-list tich-mt-4">
            <div><dt>Programme</dt><dd>{{ $review['program']->program_name ?? '-' }} ({{ $review['program']->program_code ?? ($reviewData['program_code'] ?? '-') }})</dd></div>
            <div><dt>Target intake</dt><dd>{{ $review['intakeLabel'] ?? 'Not selected' }}</dd></div>
            <div><dt>Preferred campus</dt><dd>{{ $review['campus']->campus_name ?? 'No preference' }}</dd></div>
        </dl>
    </section>

    <section class="tich-mt-6">
        <h3 class="tich-h4">Personal details</h3>
        <dl class="tich-review-list tich-mt-4">
            <div><dt>Full name</dt><dd>{{ $fullName !== '' ? $fullName : '-' }}</dd></div>
            <div><dt>Date of birth</dt><dd>{{ $reviewData['date_of_birth'] ?? '-' }}</dd></div>
            <div><dt>Gender</dt><dd>{{ $genderLabels[$reviewData['gender'] ?? ''] ?? ($reviewData['gender'] ?? '-') }}</dd></div>
            <div><dt>National ID number</dt><dd>{{ $reviewData['national_id_number'] ?? 'Not provided' }}</dd></div>
            <div><dt>Passport number</dt><dd>{{ $reviewData['passport_number'] ?? 'Not provided' }}</dd></div>
            <div><dt>Email</dt><dd>{{ $reviewData['email'] ?? '-' }}</dd></div>
            <div><dt>Phone number</dt><dd>{{ $reviewData['phone_number'] ?? '-' }}</dd></div>
            <div><dt>Home county</dt><dd>{{ $reviewData['home_county'] ?? 'Not provided' }}</dd></div>
        </dl>
    </section>

    <section class="tich-mt-6">
        <h3 class="tich-h4">Academic qualifications</h3>
        <dl class="tich-review-list tich-mt-4">
            <div><dt>Entry qualification</dt><dd>{{ $entryQualifications[$reviewData['entry_qualification'] ?? ''] ?? '-' }}</dd></div>
            <div><dt>KCSE mean grade</dt><dd>{{ $reviewData['kcse_grade'] ?? 'Not provided' }}</dd></div>
            <div><dt>KCSE year</dt><dd>{{ $reviewData['kcse_year'] ?? 'Not provided' }}</dd></div>
            <div><dt>Previous institution</dt><dd>{{ $reviewData['previous_institution'] ?? 'Not provided' }}</dd></div>
        </dl>
    </section>

    <section class="tich-mt-6">
        <h3 class="tich-h4">Sponsorship</h3>
        <dl class="tich-review-list tich-mt-4">
            <div><dt>Sponsorship type</dt><dd>{{ $sponsorshipOptions[$reviewData['sponsorship_type'] ?? ''] ?? '-' }}</dd></div>
            @if (($reviewData['sponsorship_type'] ?? '') === 'organization' || ! empty($reviewData['sponsor_organization']))
                <div><dt>Organization name</dt><dd>{{ $reviewData['sponsor_organization'] ?? 'Not provided' }}</dd></div>
                <div><dt>Organization phone</dt><dd>{{ $reviewData['sponsor_phone'] ?? 'Not provided' }}</dd></div>
                <div><dt>Organization address</dt><dd>{{ $reviewData['sponsor_address'] ?? 'Not provided' }}</dd></div>
            @endif
        </dl>
    </section>

    <section class="tich-mt-6">
        <h3 class="tich-h4">Supporting documents</h3>
        <dl class="tich-review-list tich-mt-4">
            @foreach ($documentTypes as $type => $label)
                <div>
                    <dt>{{ $label }}</dt>
                    <dd>
                        @if (! empty($documents[$type]['original_filename']))
                            {{ $documents[$type]['original_filename'] }}
                        @else
                            Not uploaded
                        @endif
                    </dd>
                </div>
            @endforeach
        </dl>
    </section>

    <section class="tich-mt-6">
        <h3 class="tich-h4">Next of kin</h3>
        <dl class="tich-review-list tich-mt-4">
            <div><dt>Full name</dt><dd>{{ $reviewData['next_of_kin_name'] ?? '-' }}</dd></div>
            <div><dt>Relationship</dt><dd>{{ $relationshipOptions[$reviewData['next_of_kin_relationship'] ?? ''] ?? '-' }}</dd></div>
            <div><dt>Address</dt><dd>{{ $reviewData['next_of_kin_address'] ?? 'Not provided' }}</dd></div>
            <div><dt>Telephone</dt><dd>{{ $reviewData['next_of_kin_phone'] ?? '-' }}</dd></div>
        </dl>
    </section>
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

<p class="tich-caption tich-mt-4">After submission you will receive an application number. Academic staff will review your application.</p>

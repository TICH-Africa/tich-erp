@php
    use App\Services\EmployeeProfileCompletenessService;

    $fieldLabels = EmployeeProfileCompletenessService::requestableFieldLabels();
    $grouped = [
        'Identity' => ['photo', 'first_name', 'middle_name', 'surname', 'date_of_birth', 'gender', 'marital_status'],
        'Contact' => ['primary_email', 'phone_number', 'alt_phone_number', 'physical_address', 'postal_address', 'postal_code', 'home_county'],
        'Emergency' => ['emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship'],
        'Qualifications' => ['qualification'],
    ];
    $selectedFields = old('fields', $selectedFields ?? []);
    $promptStaff = $promptStaff ?? null;
@endphp

<article class="tich-card tich-mt-8" id="request-profile-update">
    <h2 class="tich-h3">Request profile update</h2>
    <p class="tich-caption tich-mt-2">
        Email the employee a link to <strong>My Employee Portal</strong> with the selected items highlighted for them to update.
    </p>

    @if ($promptStaff)
        <p class="tich-text tich-mt-2"><strong>{{ $promptStaff->fullName() }}</strong> · {{ $promptStaff->employee_number }}</p>
    @endif

    <form method="POST" action="{{ $action }}" class="tich-mt-6">
        @csrf

        @unless ($promptStaff)
            <div class="tich-form-group tich-mb-4">
                <label for="profile_prompt_email" class="tich-label">Employee email</label>
                <input
                    type="email"
                    id="profile_prompt_email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    class="tich-input @error('email') tich-input--error @enderror"
                    placeholder="employee@gmail.com"
                >
                @error('email')
                    <p class="tich-field-error">{{ $message }}</p>
                @enderror
                <p class="tich-caption tich-mt-1">Use the employee's personal or organisation email on their staff record.</p>
            </div>
        @endunless

        <fieldset class="tich-profile-prompt-fields">
            <legend class="tich-label">Items to update</legend>
            @error('fields')
                <p class="tich-field-error">{{ $message }}</p>
            @enderror
            <div class="tich-grid tich-grid--2 tich-mt-2" style="gap:1rem;">
                @foreach ($grouped as $groupLabel => $keys)
                    <div class="tich-profile-prompt-fields__group">
                        <p class="tich-caption" style="font-weight:600;margin-bottom:0.35rem;">{{ $groupLabel }}</p>
                        @foreach ($keys as $key)
                            @if (isset($fieldLabels[$key]))
                                <label class="tich-check-row">
                                    <input type="checkbox" name="fields[]" value="{{ $key }}" @checked(in_array($key, $selectedFields, true))>
                                    <span>{{ $fieldLabels[$key] }}</span>
                                </label>
                            @endif
                        @endforeach
                    </div>
                @endforeach
            </div>
        </fieldset>

        <div class="tich-form-group tich-mt-4">
            <label for="profile_prompt_notes" class="tich-label">Message for employee (optional)</label>
            <textarea id="profile_prompt_notes" name="notes" rows="3" class="tich-input" maxlength="2000" placeholder="e.g. Please upload a recent passport-style photo and confirm your emergency contact.">{{ old('notes') }}</textarea>
        </div>

        <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Send profile update request</button>
    </form>
</article>

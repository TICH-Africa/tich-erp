<h2 class="tich-h3">Step 2 — Personal details</h2>
<p class="tich-text tich-mt-2">Tell us about yourself. Use the same email you will check for application updates.</p>

<div class="tich-grid tich-grid--2 tich-mt-6" style="gap: 1rem;">
    <div class="tich-form-group">
        <label for="first_name" class="tich-label">First name</label>
        <input type="text" id="first_name" name="first_name" class="tich-input" value="{{ old('first_name', $draft['first_name'] ?? '') }}" required>
        @error('first_name')<p class="tich-field-error">{{ $message }}</p>@enderror
    </div>
    <div class="tich-form-group">
        <label for="middle_name" class="tich-label">Middle name</label>
        <input type="text" id="middle_name" name="middle_name" class="tich-input" value="{{ old('middle_name', $draft['middle_name'] ?? '') }}">
    </div>
</div>

<div class="tich-form-group">
    <label for="surname" class="tich-label">Surname</label>
    <input type="text" id="surname" name="surname" class="tich-input" value="{{ old('surname', $draft['surname'] ?? '') }}" required>
    @error('surname')<p class="tich-field-error">{{ $message }}</p>@enderror
</div>

<div class="tich-grid tich-grid--2" style="gap: 1rem;">
    <div class="tich-form-group">
        <label for="date_of_birth" class="tich-label">Date of birth</label>
        <input type="date" id="date_of_birth" name="date_of_birth" class="tich-input" value="{{ old('date_of_birth', $draft['date_of_birth'] ?? '') }}" required>
        @error('date_of_birth')<p class="tich-field-error">{{ $message }}</p>@enderror
    </div>
    <div class="tich-form-group">
        <label for="gender" class="tich-label">Gender</label>
        <select id="gender" name="gender" class="tich-input" required>
            <option value="">Select</option>
            @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other', 'prefer_not_to_say' => 'Prefer not to say'] as $value => $label)
                <option value="{{ $value }}" @selected(old('gender', $draft['gender'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('gender')<p class="tich-field-error">{{ $message }}</p>@enderror
    </div>
</div>

<div class="tich-grid tich-grid--2" style="gap: 1rem;">
    <div class="tich-form-group">
        <label for="national_id_number" class="tich-label">National ID number</label>
        <input type="text" id="national_id_number" name="national_id_number" class="tich-input" value="{{ old('national_id_number', $draft['national_id_number'] ?? '') }}">
    </div>
    <div class="tich-form-group">
        <label for="passport_number" class="tich-label">Passport number</label>
        <input type="text" id="passport_number" name="passport_number" class="tich-input" value="{{ old('passport_number', $draft['passport_number'] ?? '') }}">
    </div>
</div>

<div class="tich-form-group">
    <label for="email" class="tich-label">Email address</label>
    <input type="email" id="email" name="email" class="tich-input" value="{{ old('email', $draft['email'] ?? '') }}" required>
    @error('email')<p class="tich-field-error">{{ $message }}</p>@enderror
</div>

<div class="tich-grid tich-grid--2" style="gap: 1rem;">
    <div class="tich-form-group">
        <label for="phone_number" class="tich-label">Phone number</label>
        <input type="tel" id="phone_number" name="phone_number" class="tich-input" value="{{ old('phone_number', $draft['phone_number'] ?? '') }}" required>
        @error('phone_number')<p class="tich-field-error">{{ $message }}</p>@enderror
    </div>
    <div class="tich-form-group">
        <label for="home_county" class="tich-label">Home county</label>
        <select id="home_county" name="home_county" class="tich-input">
            <option value="">Select county</option>
            @foreach ($counties as $county)
                <option value="{{ $county }}" @selected(old('home_county', $draft['home_county'] ?? '') === $county)>{{ $county }}</option>
            @endforeach
        </select>
    </div>
</div>

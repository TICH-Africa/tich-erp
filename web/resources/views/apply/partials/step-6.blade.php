<h2 class="tich-h3">Step 6 - Next of kin</h2>
<p class="tich-text tich-mt-2">Provide emergency contact information for next of kin. The first contact is required, the second one is optional.</p>

<h3 class="tich-h4 tich-mt-6">First next of kin</h3>

<div class="tich-form-group tich-mt-4">
    <label for="next_of_kin_name" class="tich-label">Full name</label>
    <input type="text" id="next_of_kin_name" name="next_of_kin_name" class="tich-input" value="{{ old('next_of_kin_name', $draft['next_of_kin_name'] ?? '') }}" required>
    @error('next_of_kin_name')<p class="tich-field-error">{{ $message }}</p>@enderror
</div>

<div class="tich-form-group tich-mt-4">
    <label for="next_of_kin_relationship" class="tich-label">Relationship</label>
    <select id="next_of_kin_relationship" name="next_of_kin_relationship" class="tich-input" required>
        <option value="">Select relationship</option>
        @foreach ($relationshipOptions as $value => $label)
            <option value="{{ $value }}" @selected(old('next_of_kin_relationship', $draft['next_of_kin_relationship'] ?? '') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @error('next_of_kin_relationship')<p class="tich-field-error">{{ $message }}</p>@enderror
</div>

<div class="tich-form-group tich-mt-4">
    <label for="next_of_kin_address" class="tich-label">Address (optional)</label>
    <input type="text" id="next_of_kin_address" name="next_of_kin_address" class="tich-input" placeholder="Street, City, County, Postal Code..." value="{{ old('next_of_kin_address', $draft['next_of_kin_address'] ?? '') }}">
    @error('next_of_kin_address')<p class="tich-field-error">{{ $message }}</p>@enderror
</div>

<div class="tich-form-group tich-mt-4">
    <label for="next_of_kin_phone" class="tich-label">Telephone number</label>
    <input type="tel" id="next_of_kin_phone" name="next_of_kin_phone" class="tich-input" value="{{ old('next_of_kin_phone', $draft['next_of_kin_phone'] ?? '') }}" required>
    @error('next_of_kin_phone')<p class="tich-field-error">{{ $message }}</p>@enderror
</div>

<h3 class="tich-h4 tich-mt-6">Second next of kin (optional)</h3>

<div class="tich-form-group tich-mt-4">
    <label for="next_of_kin2_name" class="tich-label">Full name</label>
    <input type="text" id="next_of_kin2_name" name="next_of_kin2_name" class="tich-input" value="{{ old('next_of_kin2_name', $draft['next_of_kin2_name'] ?? '') }}">
    @error('next_of_kin2_name')<p class="tich-field-error">{{ $message }}</p>@enderror
</div>

<div class="tich-form-group tich-mt-4">
    <label for="next_of_kin2_relationship" class="tich-label">Relationship</label>
    <select id="next_of_kin2_relationship" name="next_of_kin2_relationship" class="tich-input">
        <option value="">Select relationship</option>
        @foreach ($relationshipOptions as $value => $label)
            <option value="{{ $value }}" @selected(old('next_of_kin2_relationship', $draft['next_of_kin2_relationship'] ?? '') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @error('next_of_kin2_relationship')<p class="tich-field-error">{{ $message }}</p>@enderror
</div>

<div class="tich-form-group tich-mt-4">
    <label for="next_of_kin2_address" class="tich-label">Address (optional)</label>
    <input type="text" id="next_of_kin2_address" name="next_of_kin2_address" class="tich-input" placeholder="Street, City, County, Postal Code..." value="{{ old('next_of_kin2_address', $draft['next_of_kin2_address'] ?? '') }}">
    @error('next_of_kin2_address')<p class="tich-field-error">{{ $message }}</p>@enderror
</div>

<div class="tich-form-group tich-mt-4">
    <label for="next_of_kin2_phone" class="tich-label">Telephone number</label>
    <input type="tel" id="next_of_kin2_phone" name="next_of_kin2_phone" class="tich-input" value="{{ old('next_of_kin2_phone', $draft['next_of_kin2_phone'] ?? '') }}">
    @error('next_of_kin2_phone')<p class="tich-field-error">{{ $message }}</p>@enderror
</div>

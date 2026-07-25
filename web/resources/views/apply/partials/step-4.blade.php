<h2 class="tich-h3">Step 4 - Sponsorship</h2>
<p class="tich-text tich-mt-2">Specify who will sponsor your education and provide supporting details.</p>

<div class="tich-form-group tich-mt-6">
    <label for="sponsorship_type" class="tich-label">Sponsorship type</label>
    <select id="sponsorship_type" name="sponsorship_type" class="tich-input" required>
        <option value="">Select sponsorship type</option>
        @foreach ($sponsorshipOptions as $value => $label)
            <option value="{{ $value }}" @selected(old('sponsorship_type', $draft['sponsorship_type'] ?? '') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @error('sponsorship_type')<p class="tich-field-error">{{ $message }}</p>@enderror
</div>

<div id="organization-details" class="tich-form-group tich-mt-4" style="display: none;">
    <label for="sponsor_organization" class="tich-label">Organization name</label>
    <input type="text" id="sponsor_organization" name="sponsor_organization" class="tich-input" value="{{ old('sponsor_organization', $draft['sponsor_organization'] ?? '') }}">
    @error('sponsor_organization')<p class="tich-field-error">{{ $message }}</p>@enderror
</div>

<div id="sponsor-phone" class="tich-form-group tich-mt-4" style="display: none;">
    <label for="sponsor_phone" class="tich-label">Organization phone number (optional)</label>
    <input type="tel" id="sponsor_phone" name="sponsor_phone" class="tich-input" value="{{ old('sponsor_phone', $draft['sponsor_phone'] ?? '') }}">
    @error('sponsor_phone')<p class="tich-field-error">{{ $message }}</p>@enderror
</div>

<div id="sponsor-address" class="tich-form-group tich-mt-4" style="display: none;">
    <label for="sponsor_address" class="tich-label">Organization address (optional)</label>
    <input type="text" id="sponsor_address" name="sponsor_address" class="tich-input" placeholder="Street, City, County, Postal Code..." value="{{ old('sponsor_address', $draft['sponsor_address'] ?? '') }}">
    @error('sponsor_address')<p class="tich-field-error">{{ $message }}</p>@enderror
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('sponsorship_type');
    const orgDetails = document.getElementById('organization-details');
    const sponsorPhone = document.getElementById('sponsor-phone');
    const sponsorAddress = document.getElementById('sponsor-address');

    function toggleFields() {
        if (select.value === 'organization') {
            orgDetails.style.display = 'block';
            sponsorPhone.style.display = 'block';
            sponsorAddress.style.display = 'block';
        } else {
            orgDetails.style.display = 'none';
            sponsorPhone.style.display = 'none';
            sponsorAddress.style.display = 'none';
        }
    }

    select.addEventListener('change', toggleFields);
    toggleFields();
});
</script>
@php
    $categories = config('tich-payroll.employment_categories', []);
    $selected = old('employment_category', $selected ?? '');
@endphp

<div>
    <label for="employment_category" class="tich-label">Employment Category *</label>
    <select id="employment_category" name="employment_category" required class="tich-input">
        <option value="">Select category</option>
        @foreach ($categories as $value => $label)
            <option value="{{ $value }}" {{ $selected === $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
</div>

@php
    $schemes = config('tich-payroll.payroll_schemes', []);
    $selected = old('payroll_scheme', $selected ?? 'employee');
@endphp

<div>
    <label for="payroll_scheme" class="tich-label">Payroll scheme *</label>
    <select id="payroll_scheme" name="payroll_scheme" required class="tich-input">
        @foreach ($schemes as $value => $label)
            <option value="{{ $value }}" {{ $selected === $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    <p class="tich-caption tich-mt-1">Consultants and independent contractors typically use withholding tax only (no PAYE, NSSF, SHA, or AHL).</p>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var category = document.getElementById('employment_category');
        var scheme = document.getElementById('payroll_scheme');

        if (!category || !scheme) {
            return;
        }

        var withholdingCategories = @json(config('tich-payroll.withholding_employment_categories', []));

        function syncPayrollScheme() {
            if (withholdingCategories.indexOf(category.value) !== -1) {
                scheme.value = 'withholding';
            }
        }

        category.addEventListener('change', syncPayrollScheme);
    });
</script>

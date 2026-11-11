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
    <p class="tich-caption tich-mt-1">Permanent and contract staff use PAYE plus NSSF, SHA/SHIF, and Affordable Housing Levy. Interns use stipend (no statutory deductions). Volunteers are unpaid. Consultants typically use withholding tax only.</p>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var category = document.getElementById('employment_category');
        var scheme = document.getElementById('payroll_scheme');

        if (!category || !scheme) {
            return;
        }

        var withholdingCategories = @json(config('tich-payroll.withholding_employment_categories', []));
        var statutoryCategories = @json(config('tich-payroll.employee_statutory_employment_categories', ['permanent', 'contract']));
        var unpaidCategories = @json(config('tich-payroll.unpaid_employment_categories', ['volunteer']));
        var stipendCategories = @json(config('tich-payroll.stipend_employment_categories', ['intern']));

        function syncPayrollScheme() {
            if (unpaidCategories.indexOf(category.value) !== -1) {
                scheme.value = 'unpaid';
            } else if (stipendCategories.indexOf(category.value) !== -1) {
                scheme.value = 'stipend';
            } else if (statutoryCategories.indexOf(category.value) !== -1) {
                scheme.value = 'employee';
            } else if (withholdingCategories.indexOf(category.value) !== -1) {
                scheme.value = 'withholding';
            }
        }

        category.addEventListener('change', syncPayrollScheme);
    });
</script>

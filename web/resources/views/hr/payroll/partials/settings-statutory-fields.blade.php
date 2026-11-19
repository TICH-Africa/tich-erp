@php
    /** @var \App\Models\PayrollKenyaStatutorySetting $statutory */
@endphp

<div class="tich-card tich-table-panel tich-mb-8">
    <p class="tich-caption tich-mb-4">Employee NSSF (tiered), SHA/SHIF (floor + %), and Affordable Housing Levy. Pensionable pay defaults to monthly gross unless overridden per payslip run.</p>

    <h3 class="tich-h4 tich-mb-3">NSSF</h3>
    <div class="tich-grid tich-grid--3 tich-mb-6">
        <div>
            <label for="statutory_nssf_rate_percent" class="tich-label">Contribution rate (%)</label>
            <input type="number" step="0.01" min="0" max="100" class="tich-input" id="statutory_nssf_rate_percent" name="kenya_statutory[nssf_rate_percent]" value="{{ old('kenya_statutory.nssf_rate_percent', rtrim(rtrim(number_format((float) $statutory->nssf_rate_percent, 4), '0'), '.')) }}" required form="tax-settings-form">
        </div>
        <div>
            <label for="statutory_nssf_tier1_ceiling" class="tich-label">Tier I ceiling (KES)</label>
            <input type="number" step="0.01" min="0" class="tich-input" id="statutory_nssf_tier1_ceiling" name="kenya_statutory[nssf_tier1_ceiling]" value="{{ old('kenya_statutory.nssf_tier1_ceiling', number_format((float) $statutory->nssf_tier1_ceiling, 2, '.', '')) }}" required form="tax-settings-form">
        </div>
        <div>
            <label for="statutory_nssf_tier1_max" class="tich-label">Tier I max employee (KES)</label>
            <input type="number" step="0.01" min="0" class="tich-input" id="statutory_nssf_tier1_max" name="kenya_statutory[nssf_tier1_max_employee]" value="{{ old('kenya_statutory.nssf_tier1_max_employee', number_format((float) $statutory->nssf_tier1_max_employee, 2, '.', '')) }}" required form="tax-settings-form">
        </div>
        <div>
            <label for="statutory_nssf_tier2_ceiling" class="tich-label">Tier II ceiling (pensionable KES)</label>
            <input type="number" step="0.01" min="0" class="tich-input" id="statutory_nssf_tier2_ceiling" name="kenya_statutory[nssf_tier2_ceiling]" value="{{ old('kenya_statutory.nssf_tier2_ceiling', number_format((float) $statutory->nssf_tier2_ceiling, 2, '.', '')) }}" required form="tax-settings-form">
        </div>
        <div>
            <label for="statutory_nssf_tier2_max" class="tich-label">Tier II max employee (KES)</label>
            <input type="number" step="0.01" min="0" class="tich-input" id="statutory_nssf_tier2_max" name="kenya_statutory[nssf_tier2_max_employee]" value="{{ old('kenya_statutory.nssf_tier2_max_employee', number_format((float) $statutory->nssf_tier2_max_employee, 2, '.', '')) }}" required form="tax-settings-form">
        </div>
        <div>
            <label for="statutory_nssf_max" class="tich-label">Combined max employee (KES)</label>
            <input type="number" step="0.01" min="0" class="tich-input" id="statutory_nssf_max" name="kenya_statutory[nssf_max_employee]" value="{{ old('kenya_statutory.nssf_max_employee', number_format((float) $statutory->nssf_max_employee, 2, '.', '')) }}" required form="tax-settings-form">
        </div>
    </div>

    <h3 class="tich-h4 tich-mb-3">SHA / SHIF</h3>
    <div class="tich-grid tich-grid--3 tich-mb-6">
        <div>
            <label for="statutory_shif_rate" class="tich-label">Deduction rate (% of gross)</label>
            <input type="number" step="0.01" min="0" max="100" class="tich-input" id="statutory_shif_rate" name="kenya_statutory[shif_rate_percent]" value="{{ old('kenya_statutory.shif_rate_percent', rtrim(rtrim(number_format((float) $statutory->shif_rate_percent, 4), '0'), '.')) }}" required form="tax-settings-form">
        </div>
        <div>
            <label for="statutory_shif_floor" class="tich-label">Monthly minimum (KES)</label>
            <input type="number" step="0.01" min="0" class="tich-input" id="statutory_shif_floor" name="kenya_statutory[shif_monthly_floor]" value="{{ old('kenya_statutory.shif_monthly_floor', number_format((float) $statutory->shif_monthly_floor, 2, '.', '')) }}" required form="tax-settings-form">
        </div>
        <div>
            <label for="statutory_shif_floor_gross" class="tich-label">Flat floor if gross up to (KES)</label>
            <input type="number" step="0.01" min="0" class="tich-input" id="statutory_shif_floor_gross" name="kenya_statutory[shif_floor_applies_up_to_gross]" value="{{ old('kenya_statutory.shif_floor_applies_up_to_gross', number_format((float) $statutory->shif_floor_applies_up_to_gross, 2, '.', '')) }}" required form="tax-settings-form">
        </div>
    </div>

    <h3 class="tich-h4 tich-mb-3">Affordable Housing Levy (AHL)</h3>
    <div class="tich-grid tich-grid--3">
        <div>
            <label for="statutory_ahl_rate" class="tich-label">Employee rate (% of gross cash)</label>
            <input type="number" step="0.01" min="0" max="100" class="tich-input" id="statutory_ahl_rate" name="kenya_statutory[ahl_rate_percent]" value="{{ old('kenya_statutory.ahl_rate_percent', rtrim(rtrim(number_format((float) $statutory->ahl_rate_percent, 4), '0'), '.')) }}" required form="tax-settings-form">
        </div>
    </div>
</div>
@php
    use App\Models\PayrollStatutoryBracket;

    /** @var array<string, \Illuminate\Support\Collection<int, PayrollStatutoryBracket>> $statutoryBrackets */
    $calculationLabels = [
        'percent' => 'Percent of base',
        'fixed' => 'Fixed amount',
        'percent_capped' => 'Percent with cap',
        'percent_floor' => 'Percent with minimum',
    ];
@endphp

<style>
    .payroll-statutory-subtabs__nav {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
    @media (max-width: 720px) {
        .payroll-statutory-subtabs__nav { grid-template-columns: 1fr; }
    }
    .payroll-statutory-bracket-table input[type="number"],
    .payroll-statutory-bracket-table input[type="text"] {
        width: 100%;
        min-width: 5rem;
    }
    .payroll-statutory-bracket-table .tich-input--compact {
        padding: 0.35rem 0.5rem;
        font-size: 0.875rem;
    }
</style>

<div class="tich-card tich-mb-8">
    <p class="tich-caption tich-mb-4">
        NSSF, SHA/SHIF, and AHL each use their own brackets (not the same as PAYE income tax bands).
        Edit the rows below; payroll uses these tables when calculating deductions.
    </p>

    <div class="tich-tabs" data-statutory-subtabs>
        <div class="tich-tabs__nav payroll-statutory-subtabs__nav">
            <button type="button" class="tich-tabs__btn is-active" data-statutory-subtab="nssf">NSSF tiers</button>
            <button type="button" class="tich-tabs__btn" data-statutory-subtab="shif">SHA / SHIF</button>
            <button type="button" class="tich-tabs__btn" data-statutory-subtab="ahl">AHL</button>
        </div>

        @foreach ([
            PayrollStatutoryBracket::SCHEME_NSSF => 'NSSF pension tiers (employee)',
            PayrollStatutoryBracket::SCHEME_SHIF => 'SHA / SHIF (employee)',
            PayrollStatutoryBracket::SCHEME_AHL => 'Affordable Housing Levy (employee)',
        ] as $scheme => $heading)
            @php
                $rows = $statutoryBrackets[$scheme] ?? collect();
            @endphp
            <div class="tich-tabs__panel {{ $scheme === PayrollStatutoryBracket::SCHEME_NSSF ? 'is-active' : '' }}" data-statutory-panel="{{ $scheme }}">
                <h3 class="tich-h4 tich-mb-4">{{ $heading }}</h3>
                <div class="tich-table-wrap" style="overflow-x: auto;">
                    <table class="tich-admin-table payroll-statutory-bracket-table">
                        <thead>
                            <tr>
                                <th>Label</th>
                                <th>Min (KES)</th>
                                <th>Max (KES)</th>
                                <th>Calculation</th>
                                <th>Rate %</th>
                                <th>Fixed (KES)</th>
                                <th>Cap (KES)</th>
                                <th>Active</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $index => $row)
                                <tr>
                                    <td>
                                        <input type="hidden" name="statutory_brackets[{{ $scheme }}][{{ $index }}][id]" value="{{ $row->id }}" form="tax-settings-form">
                                        <input type="text" class="tich-input tich-input--compact" name="statutory_brackets[{{ $scheme }}][{{ $index }}][label]" value="{{ old('statutory_brackets.'.$scheme.'.'.$index.'.label', $row->label) }}" required form="tax-settings-form">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" class="tich-input tich-input--compact" name="statutory_brackets[{{ $scheme }}][{{ $index }}][min_amount]" value="{{ old('statutory_brackets.'.$scheme.'.'.$index.'.min_amount', number_format((float) $row->min_amount, 2, '.', '')) }}" form="tax-settings-form">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" class="tich-input tich-input--compact" name="statutory_brackets[{{ $scheme }}][{{ $index }}][max_amount]" value="{{ old('statutory_brackets.'.$scheme.'.'.$index.'.max_amount', $row->max_amount !== null ? number_format((float) $row->max_amount, 2, '.', '') : '') }}" placeholder="No limit" form="tax-settings-form">
                                    </td>
                                    <td>
                                        <select class="tich-input tich-input--compact" name="statutory_brackets[{{ $scheme }}][{{ $index }}][calculation]" form="tax-settings-form">
                                            @foreach ($calculationLabels as $value => $label)
                                                <option value="{{ $value }}" @selected(old('statutory_brackets.'.$scheme.'.'.$index.'.calculation', $row->calculation) === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.0001" min="0" max="100" class="tich-input tich-input--compact" name="statutory_brackets[{{ $scheme }}][{{ $index }}][rate_percent]" value="{{ old('statutory_brackets.'.$scheme.'.'.$index.'.rate_percent', $row->rate_percent !== null ? rtrim(rtrim(number_format((float) $row->rate_percent, 4), '0'), '.') : '') }}" form="tax-settings-form">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" class="tich-input tich-input--compact" name="statutory_brackets[{{ $scheme }}][{{ $index }}][fixed_amount]" value="{{ old('statutory_brackets.'.$scheme.'.'.$index.'.fixed_amount', $row->fixed_amount !== null ? number_format((float) $row->fixed_amount, 2, '.', '') : '') }}" form="tax-settings-form">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" class="tich-input tich-input--compact" name="statutory_brackets[{{ $scheme }}][{{ $index }}][cap_amount]" value="{{ old('statutory_brackets.'.$scheme.'.'.$index.'.cap_amount', $row->cap_amount !== null ? number_format((float) $row->cap_amount, 2, '.', '') : '') }}" form="tax-settings-form">
                                    </td>
                                    <td>
                                        <label class="tich-caption">
                                            <input type="hidden" name="statutory_brackets[{{ $scheme }}][{{ $index }}][is_active]" value="0" form="tax-settings-form">
                                            <input type="checkbox" name="statutory_brackets[{{ $scheme }}][{{ $index }}][is_active]" value="1" @checked(old('statutory_brackets.'.$scheme.'.'.$index.'.is_active', $row->is_active)) form="tax-settings-form">
                                            Active
                                        </label>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="tich-table-empty">No brackets yet — save settings once to seed defaults, or visit this page after migration.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-statutory-subtabs]').forEach(function (root) {
            root.querySelectorAll('[data-statutory-subtab]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var name = btn.getAttribute('data-statutory-subtab');
                    root.querySelectorAll('[data-statutory-subtab]').forEach(function (b) { b.classList.remove('is-active'); });
                    root.querySelectorAll('[data-statutory-panel]').forEach(function (p) { p.classList.remove('is-active'); });
                    btn.classList.add('is-active');
                    var panel = root.querySelector('[data-statutory-panel="' + name + '"]');
                    if (panel) {
                        panel.classList.add('is-active');
                    }
                });
            });
        });
    });
</script>
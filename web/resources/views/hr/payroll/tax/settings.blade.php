@extends('layouts.hr')

@section('title', 'KRA Tax Settings')

@section('hr-content')
    <div class="tich-mb-8">
        <div class="tich-flex tich-flex--between tich-flex--start">
            <div>
                <h1 class="tich-h1">KRA tax settings</h1>
                <p class="tich-text tich-mt-2">Edit monthly PAYE bands and statutory deduction rates (NSSF, SHA/SHIF, housing levy, personal relief).</p>
            </div>
            <a href="{{ route('hr.payroll.tax.index') }}" class="tich-btn tich-btn-secondary">&larr; Back to calculator</a>
        </div>
    </div>

    <form method="POST" action="{{ route('hr.payroll.tax.settings.update') }}">
        @csrf
        @method('PUT')

        <div class="tich-card tich-table-panel tich-mb-8">
            <div class="tich-flex tich-flex--between tich-mb-4">
                <h2 class="tich-h3">PAYE tax bands (KRA)</h2>
                <button type="button" class="tich-btn tich-btn-ghost" id="add-band-row">+ Add band</button>
            </div>
            <div class="tich-table-wrap">
                <table class="tich-admin-table" id="bands-table">
                    <thead>
                        <tr>
                            <th>Label</th>
                            <th>Min (KES)</th>
                            <th>Max (KES)</th>
                            <th>Rate %</th>
                            <th>Order</th>
                            <th>Active</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bands as $index => $band)
                            <tr>
                                <td>
                                    <input type="hidden" name="bands[{{ $index }}][id]" value="{{ $band->id }}">
                                    <input type="text" name="bands[{{ $index }}][label]" value="{{ old('bands.'.$index.'.label', $band->label) }}" class="tich-input" required>
                                </td>
                                <td><input type="number" name="bands[{{ $index }}][min_amount]" value="{{ old('bands.'.$index.'.min_amount', $band->min_amount) }}" min="0" step="0.01" class="tich-input" required></td>
                                <td><input type="number" name="bands[{{ $index }}][max_amount]" value="{{ old('bands.'.$index.'.max_amount', $band->max_amount) }}" min="0" step="0.01" class="tich-input" placeholder="No limit"></td>
                                <td><input type="number" name="bands[{{ $index }}][rate_percent]" value="{{ old('bands.'.$index.'.rate_percent', $band->rate_percent) }}" min="0" max="100" step="0.01" class="tich-input" required></td>
                                <td><input type="number" name="bands[{{ $index }}][display_order]" value="{{ old('bands.'.$index.'.display_order', $band->display_order) }}" min="0" class="tich-input"></td>
                                <td><input type="checkbox" name="bands[{{ $index }}][is_active]" value="1" {{ old('bands.'.$index.'.is_active', $band->is_active) ? 'checked' : '' }}></td>
                                <td><button type="button" class="tich-btn tich-btn-ghost remove-band-row">Remove</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tich-card tich-table-panel tich-mb-8">
            <h2 class="tich-h3 tich-mb-4">Statutory deductions from salary</h2>
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Label</th>
                            <th>Employee rate %</th>
                            <th>Employer rate %</th>
                            <th>Fixed (KES)</th>
                            <th>Floor</th>
                            <th>Ceiling</th>
                            <th>Notes</th>
                            <th>Active</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rates as $index => $rate)
                            <tr>
                                <td class="tich-caption">{{ $rate->code }}</td>
                                <td>
                                    <input type="hidden" name="rates[{{ $index }}][id]" value="{{ $rate->id }}">
                                    <input type="text" name="rates[{{ $index }}][label]" value="{{ old('rates.'.$index.'.label', $rate->label) }}" class="tich-input" required>
                                </td>
                                <td><input type="number" name="rates[{{ $index }}][rate_percent]" value="{{ old('rates.'.$index.'.rate_percent', $rate->rate_percent) }}" min="0" max="100" step="0.0001" class="tich-input"></td>
                                <td><input type="number" name="rates[{{ $index }}][employer_rate_percent]" value="{{ old('rates.'.$index.'.employer_rate_percent', $rate->employer_rate_percent) }}" min="0" max="100" step="0.0001" class="tich-input"></td>
                                <td><input type="number" name="rates[{{ $index }}][fixed_amount]" value="{{ old('rates.'.$index.'.fixed_amount', $rate->fixed_amount) }}" min="0" step="0.01" class="tich-input"></td>
                                <td><input type="number" name="rates[{{ $index }}][floor_amount]" value="{{ old('rates.'.$index.'.floor_amount', $rate->floor_amount) }}" min="0" step="0.01" class="tich-input"></td>
                                <td><input type="number" name="rates[{{ $index }}][ceiling_amount]" value="{{ old('rates.'.$index.'.ceiling_amount', $rate->ceiling_amount) }}" min="0" step="0.01" class="tich-input"></td>
                                <td><input type="text" name="rates[{{ $index }}][notes]" value="{{ old('rates.'.$index.'.notes', $rate->notes) }}" class="tich-input"></td>
                                <td><input type="checkbox" name="rates[{{ $index }}][is_active]" value="1" {{ old('rates.'.$index.'.is_active', $rate->is_active) ? 'checked' : '' }}></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <button type="submit" class="tich-btn tich-btn-primary">Save tax settings</button>
    </form>

    <script>
        (function () {
            const table = document.getElementById('bands-table')?.querySelector('tbody');
            let nextIndex = {{ $bands->count() }};

            document.getElementById('add-band-row')?.addEventListener('click', function () {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><input type="text" name="bands[${nextIndex}][label]" class="tich-input" required placeholder="Band label"></td>
                    <td><input type="number" name="bands[${nextIndex}][min_amount]" min="0" step="0.01" class="tich-input" required></td>
                    <td><input type="number" name="bands[${nextIndex}][max_amount]" min="0" step="0.01" class="tich-input" placeholder="No limit"></td>
                    <td><input type="number" name="bands[${nextIndex}][rate_percent]" min="0" max="100" step="0.01" class="tich-input" required></td>
                    <td><input type="number" name="bands[${nextIndex}][display_order]" value="${nextIndex}" min="0" class="tich-input"></td>
                    <td><input type="checkbox" name="bands[${nextIndex}][is_active]" value="1" checked></td>
                    <td><button type="button" class="tich-btn tich-btn-ghost remove-band-row">Remove</button></td>
                `;
                table.appendChild(row);
                nextIndex++;
            });

            table?.addEventListener('click', function (event) {
                if (event.target.classList.contains('remove-band-row')) {
                    event.target.closest('tr')?.remove();
                }
            });
        })();
    </script>
@endsection

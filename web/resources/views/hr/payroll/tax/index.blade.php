@extends('layouts.hr')

@section('title', 'Payroll Tax Calculator')

@section('hr-content')
    <div class="tich-mb-8">
        <div class="tich-flex tich-flex--between tich-flex--start">
            <div>
                <h1 class="tich-h1">Payroll tax calculator</h1>
                <p class="tich-text tich-mt-2">Compute KRA PAYE, NSSF, SHA/SHIF, and housing levy from net or gross salary using editable tax bands.</p>
            </div>
            <a href="{{ route('hr.payroll.tax.settings') }}" class="tich-btn tich-btn-secondary">Edit tax bands & rates</a>
        </div>
    </div>

    <div class="tich-grid tich-grid--2 tich-mb-8">
        <div class="tich-card">
            <h2 class="tich-h3">Calculate salary breakdown</h2>
            <form method="POST" action="{{ route('hr.payroll.tax.calculate') }}" class="tich-mt-4">
                @csrf

                <div class="tich-form-group">
                    <label class="tich-label">Calculation mode</label>
                    <div class="tich-flex" style="gap: 1rem; flex-wrap: wrap;">
                        <label class="tich-text"><input type="radio" name="mode" value="net" {{ ($input['mode'] ?? 'net') === 'net' ? 'checked' : '' }}> From net salary (compute gross)</label>
                        <label class="tich-text"><input type="radio" name="mode" value="gross" {{ ($input['mode'] ?? '') === 'gross' ? 'checked' : '' }}> From gross salary</label>
                    </div>
                </div>

                <div class="tich-form-group">
                    <label for="staff_id" class="tich-label">Staff member (optional)</label>
                    <select id="staff_id" name="staff_id" class="tich-select">
                        <option value="">— Manual entry —</option>
                        @foreach ($staff as $member)
                            <option value="{{ $member->id }}" data-gross="{{ $member->gross_monthly_salary }}" {{ (string) ($input['staff_id'] ?? '') === (string) $member->id ? 'selected' : '' }}>
                                {{ $member->fullName() }} ({{ $member->employee_number }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="tich-form-group">
                    <label for="employee_name" class="tich-label">Employee name (for report)</label>
                    <input type="text" id="employee_name" name="employee_name" value="{{ $input['employee_name'] ?? '' }}" class="tich-input" placeholder="Optional display name on PDF">
                </div>

                <div class="tich-form-group">
                    <label for="amount" class="tich-label">Salary amount (KES) *</label>
                    <input type="number" id="amount" name="amount" value="{{ $input['amount'] ?? '' }}" min="0" step="0.01" required class="tich-input" placeholder="Enter net or gross monthly amount">
                </div>

                <div class="tich-grid tich-grid--2">
                    <div class="tich-form-group">
                        <label for="allowances" class="tich-label">Allowances (KES)</label>
                        <input type="number" id="allowances" name="allowances" value="{{ $input['allowances'] ?? 0 }}" min="0" step="0.01" class="tich-input">
                    </div>
                    <div class="tich-form-group">
                        <label for="other_deductions" class="tich-label">Other deductions (KES)</label>
                        <input type="number" id="other_deductions" name="other_deductions" value="{{ $input['other_deductions'] ?? 0 }}" min="0" step="0.01" class="tich-input">
                    </div>
                </div>

                <button type="submit" class="tich-btn tich-btn-primary">Calculate</button>
            </form>
        </div>

        @if ($breakdown)
            <div class="tich-card">
                <h2 class="tich-h3">Summary</h2>
                <dl class="tich-mt-4">
                    <div class="tich-flex tich-flex--between tich-mt-2"><dt>Gross salary</dt><dd><strong>KES {{ number_format($breakdown['gross_salary'], 2) }}</strong></dd></div>
                    <div class="tich-flex tich-flex--between tich-mt-2"><dt>Taxable income</dt><dd>KES {{ number_format($breakdown['taxable_income'], 2) }}</dd></div>
                    <div class="tich-flex tich-flex--between tich-mt-2"><dt>PAYE (after relief)</dt><dd>KES {{ number_format($breakdown['paye'], 2) }}</dd></div>
                    <div class="tich-flex tich-flex--between tich-mt-2"><dt>Total deductions</dt><dd>KES {{ number_format($breakdown['total_deductions'], 2) }}</dd></div>
                    <div class="tich-flex tich-flex--between tich-mt-2"><dt>Net salary</dt><dd><strong>KES {{ number_format($breakdown['net_salary'], 2) }}</strong></dd></div>
                    <div class="tich-flex tich-flex--between tich-mt-2"><dt>Employer cost</dt><dd>KES {{ number_format($breakdown['total_employer_cost'], 2) }}</dd></div>
                </dl>

                @if (($breakdown['mode'] ?? '') === 'net')
                    <p class="tich-caption tich-mt-4">Computed gross from target net: KES {{ number_format($breakdown['computed_gross'] ?? $breakdown['basic_salary'], 2) }}</p>
                @endif

                <div class="tich-mt-4" style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                    <a href="{{ route('hr.payroll.tax.report', request()->query()) }}" class="tich-btn tich-btn-secondary" target="_blank">Preview report</a>
                    <a href="{{ route('hr.payroll.tax.report.pdf', request()->query()) }}" class="tich-btn tich-btn-primary">Download PDF</a>
                </div>
            </div>
        @endif
    </div>

    @if ($breakdown)
        <div class="tich-grid tich-grid--2">
            <div class="tich-card tich-table-panel">
                <h2 class="tich-h3 tich-mb-4">Employee deductions</h2>
                <div class="tich-table-wrap">
                    <table class="tich-admin-table">
                        <thead>
                            <tr>
                                <th>Deduction</th>
                                <th>Base (KES)</th>
                                <th>Rate</th>
                                <th>Amount (KES)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($breakdown['deductions'] as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td class="tich-caption">{{ $row['base'] !== null ? number_format($row['base'], 2) : '—' }}</td>
                                    <td class="tich-caption">
                                        @if (($row['code'] ?? '') === 'paye' && ! empty($row['relief']))
                                            Bands + relief
                                        @elseif ($row['rate'])
                                            {{ rtrim(rtrim(number_format($row['rate'], 2), '0'), '.') }}%
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ number_format($row['amount'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tich-card tich-table-panel">
                <h2 class="tich-h3 tich-mb-4">PAYE band breakdown (KRA)</h2>
                <div class="tich-table-wrap">
                    <table class="tich-admin-table">
                        <thead>
                            <tr>
                                <th>Band</th>
                                <th>Taxable (KES)</th>
                                <th>Rate</th>
                                <th>Tax (KES)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($breakdown['band_breakdown'] as $line)
                                <tr>
                                    <td>{{ $line['label'] }}</td>
                                    <td class="tich-caption">{{ number_format($line['taxable_amount'], 2) }}</td>
                                    <td class="tich-caption">{{ rtrim(rtrim(number_format($line['rate_percent'], 2), '0'), '.') }}%</td>
                                    <td>{{ number_format($line['tax'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="tich-caption">No taxable income in configured bands.</td></tr>
                            @endforelse
                            <tr>
                                <td colspan="3"><strong>Before personal relief</strong></td>
                                <td><strong>{{ number_format($breakdown['paye_before_relief'], 2) }}</strong></td>
                            </tr>
                            <tr>
                                <td colspan="3">Personal relief</td>
                                <td>- {{ number_format($breakdown['personal_relief'], 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="3"><strong>PAYE payable</strong></td>
                                <td><strong>{{ number_format($breakdown['paye'], 2) }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if (! empty($breakdown['employer_contributions']))
            <div class="tich-card tich-table-panel tich-mt-8">
                <h2 class="tich-h3 tich-mb-4">Employer contributions</h2>
                <div class="tich-table-wrap">
                    <table class="tich-admin-table">
                        <thead>
                            <tr><th>Contribution</th><th>Amount (KES)</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($breakdown['employer_contributions'] as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td>{{ number_format($row['amount'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif

    <script>
        document.getElementById('staff_id')?.addEventListener('change', function () {
            const option = this.options[this.selectedIndex];
            const gross = option.getAttribute('data-gross');
            const name = option.textContent.split(' (')[0].trim();
            if (gross && document.querySelector('input[name="mode"]:checked')?.value === 'gross') {
                document.getElementById('amount').value = gross;
            }
            if (name && name !== '— Manual entry —') {
                document.getElementById('employee_name').value = name;
            }
        });
    </script>
@endsection

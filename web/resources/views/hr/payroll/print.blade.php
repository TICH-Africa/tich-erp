@extends('layouts.print-document')

@section('document-content')
    @php
        $activeBandLines = collect($breakdown['band_breakdown'] ?? [])->filter(
            fn ($line) => ($line['tax'] ?? 0) > 0 || ($line['taxable_amount'] ?? 0) > 0
        );
    @endphp

    <div class="tich-payslip">
        <div class="tich-payslip__employee">
            <div>
                <span class="tich-payslip__label">Employee</span>
                <strong>{{ $breakdown['employee_name'] ?? 'Staff member' }}</strong>
            </div>
            <div>
                <span class="tich-payslip__label">Employee no.</span>
                <strong>{{ $breakdown['employee_number'] ?? '-' }}</strong>
            </div>
            <div>
                <span class="tich-payslip__label">Pay period</span>
                <strong>{{ $payPeriod ?? now()->format('F Y') }}</strong>
            </div>
        </div>

        <div class="tich-payslip__grid">
            <section class="tich-payslip__panel">
                <h2 class="tich-payslip__panel-title">Earnings</h2>
                <table class="tich-payslip__table">
                    <tbody>
                        <tr>
                            <th>Basic salary</th>
                            <td class="num">KES {{ number_format($breakdown['basic_salary'], 2) }}</td>
                        </tr>
                        <tr>
                            <th>Allowances</th>
                            <td class="num">KES {{ number_format($breakdown['allowances'], 2) }}</td>
                        </tr>
                        <tr class="tich-payslip__total-row">
                            <th>Gross pay</th>
                            <td class="num"><strong>KES {{ number_format($breakdown['gross_salary'], 2) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section class="tich-payslip__panel">
                <h2 class="tich-payslip__panel-title">Deductions</h2>
                <table class="tich-payslip__table">
                    <tbody>
                        @foreach ($breakdown['deductions'] as $row)
                            <tr>
                                <th>{{ $row['label'] }}</th>
                                <td class="num">KES {{ number_format($row['amount'], 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="tich-payslip__total-row">
                            <th>Total deductions</th>
                            <td class="num"><strong>KES {{ number_format($breakdown['total_deductions'], 2) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>

        <div class="tich-payslip__net">
            <span>Net pay</span>
            <strong>KES {{ number_format($breakdown['net_salary'], 2) }}</strong>
        </div>

        @if ($activeBandLines->isNotEmpty())
            <section class="tich-payslip__detail">
                <h2 class="tich-payslip__detail-title">PAYE band summary</h2>
                <table class="tich-payslip__table tich-payslip__table--compact">
                    <thead>
                        <tr>
                            <th>Band</th>
                            <th class="num">Taxable (KES)</th>
                            <th class="num">Rate</th>
                            <th class="num">Tax (KES)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($activeBandLines as $line)
                            <tr>
                                <td>{{ $line['label'] }}</td>
                                <td class="num">{{ number_format($line['taxable_amount'], 2) }}</td>
                                <td class="num">{{ rtrim(rtrim(number_format($line['rate_percent'], 2), '0'), '.') }}%</td>
                                <td class="num">{{ number_format($line['tax'], 2) }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="3">PAYE before relief</td>
                            <td class="num">{{ number_format($breakdown['paye_before_relief'], 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="3">Personal relief</td>
                            <td class="num">- {{ number_format($breakdown['personal_relief'], 2) }}</td>
                        </tr>
                        <tr class="tich-payslip__total-row">
                            <td colspan="3"><strong>PAYE payable</strong></td>
                            <td class="num"><strong>{{ number_format($breakdown['paye'], 2) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </section>
        @endif

        <div class="tich-payslip__footer-grid">
            <div class="tich-payslip__stat">
                <span class="tich-payslip__label">Taxable income</span>
                <strong>KES {{ number_format($breakdown['taxable_income'], 2) }}</strong>
            </div>
            <div class="tich-payslip__stat">
                <span class="tich-payslip__label">Employer cost</span>
                <strong>KES {{ number_format($breakdown['total_employer_cost'], 2) }}</strong>
            </div>
            @if (! empty($breakdown['employer_contributions']))
                <div class="tich-payslip__stat tich-payslip__stat--wide">
                    <span class="tich-payslip__label">Employer contributions</span>
                    <strong>
                        @foreach ($breakdown['employer_contributions'] as $row)
                            {{ $row['label'] }} KES {{ number_format($row['amount'], 2) }}@if (! $loop->last) · @endif
                        @endforeach
                    </strong>
                </div>
            @endif
        </div>

        <p class="tich-payslip__note">Computer-generated payslip per configured KRA PAYE bands and statutory rates. Not valid without institution payroll authorisation.</p>
    </div>
@endsection

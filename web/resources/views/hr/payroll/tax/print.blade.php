@extends('layouts.print-document')

@section('document-content')
    <section class="tich-doc-section">
        <h2>Salary summary</h2>
        <table class="tich-doc-table">
            <tbody>
                <tr><th>Basic salary</th><td>KES {{ number_format($breakdown['basic_salary'], 2) }}</td></tr>
                <tr><th>Allowances</th><td>KES {{ number_format($breakdown['allowances'], 2) }}</td></tr>
                <tr><th>Gross salary</th><td><strong>KES {{ number_format($breakdown['gross_salary'], 2) }}</strong></td></tr>
                <tr><th>Taxable income (PAYE)</th><td>KES {{ number_format($breakdown['taxable_income'], 2) }}</td></tr>
                <tr><th>Total employee deductions</th><td>KES {{ number_format($breakdown['total_deductions'], 2) }}</td></tr>
                <tr><th>Net salary</th><td><strong>KES {{ number_format($breakdown['net_salary'], 2) }}</strong></td></tr>
                <tr><th>Total employer cost</th><td>KES {{ number_format($breakdown['total_employer_cost'], 2) }}</td></tr>
            </tbody>
        </table>
    </section>

    <section class="tich-doc-section">
        <h2>Employee deductions</h2>
        <table class="tich-doc-table">
            <thead>
                <tr>
                    <th>Deduction</th>
                    <th>Base (KES)</th>
                    <th>Rate / note</th>
                    <th>Amount (KES)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($breakdown['deductions'] as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td>{{ $row['base'] !== null ? number_format($row['base'], 2) : '—' }}</td>
                        <td>
                            @if (($row['code'] ?? '') === 'paye')
                                KRA bands; relief KES {{ number_format($breakdown['personal_relief'], 2) }}
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
    </section>

    <section class="tich-doc-section">
        <h2>KRA PAYE band breakdown</h2>
        <table class="tich-doc-table">
            <thead>
                <tr>
                    <th>Band</th>
                    <th>Taxable amount (KES)</th>
                    <th>Rate</th>
                    <th>Tax (KES)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($breakdown['band_breakdown'] as $line)
                    <tr>
                        <td>{{ $line['label'] }}</td>
                        <td>{{ number_format($line['taxable_amount'], 2) }}</td>
                        <td>{{ rtrim(rtrim(number_format($line['rate_percent'], 2), '0'), '.') }}%</td>
                        <td>{{ number_format($line['tax'], 2) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="3"><strong>PAYE before personal relief</strong></td>
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
    </section>

    @if (! empty($breakdown['employer_contributions']))
        <section class="tich-doc-section">
            <h2>Employer contributions</h2>
            <table class="tich-doc-table">
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
        </section>
    @endif

    <section class="tich-doc-section">
        <h2>Configured statutory rates (reference)</h2>
        <table class="tich-doc-table">
            <thead>
                <tr><th>Item</th><th>Employee</th><th>Employer</th><th>Notes</th></tr>
            </thead>
            <tbody>
                @foreach ($breakdown['statutory_rates'] as $rate)
                    <tr>
                        <td>{{ $rate['label'] }}</td>
                        <td>
                            @if ($rate['fixed_amount'])
                                KES {{ number_format($rate['fixed_amount'], 2) }}
                            @elseif ($rate['rate_percent'])
                                {{ rtrim(rtrim(number_format($rate['rate_percent'], 4), '0'), '.') }}%
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $rate['employer_rate_percent'] ? rtrim(rtrim(number_format($rate['employer_rate_percent'], 4), '0'), '.').'%' : '—' }}</td>
                        <td class="tich-caption">
                            @if ($rate['ceiling_amount'])
                                Ceiling KES {{ number_format($rate['ceiling_amount'], 2) }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
@endsection

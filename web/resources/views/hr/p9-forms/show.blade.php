@extends('layouts.hr')

@section('title', "P9A - {$employee->fullName()} ({$year})")

@section('hr-content')
    <x-page-toolbar title="P9A Tax Deduction Card" meta="{{ $employee->fullName() }} &mdash; Year {{ $year }}">
        <x-slot:actions>
            <a href="{{ route('hr.p9-forms.download', ['staff' => $employee, 'year' => $year]) }}" class="tich-btn tich-btn-primary">Download Excel</a>
            <a href="{{ route('hr.p9-forms.index', ['year' => $year]) }}" class="tich-btn tich-btn-secondary">Back to list</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-mb-6">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem;">
            <div>
                <p class="tich-caption">Employer</p>
                <p class="tich-text"><strong>Tropical Institute of Community Health Development, (TICH)</strong></p>
                <p class="tich-caption" style="margin-top:0.25rem;">PIN: P051129554G</p>
            </div>
            <div>
                <p class="tich-caption">Employee</p>
                <p class="tich-text"><strong>{{ $employee->fullName() }}</strong></p>
                <p class="tich-caption" style="margin-top:0.25rem;">
                    PIN: {{ $employee->kra_pin ?? 'Not set' }} &middot; No: {{ $employee->employee_number }}
                </p>
            </div>
        </div>

        <div class="tich-table-wrap">
            <table class="tich-admin-table" style="font-size:0.75rem;">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th style="text-align:right;">Basic Salary<br><span class="tich-caption">A</span></th>
                        <th style="text-align:right;">Benefits<br><span class="tich-caption">B</span></th>
                        <th style="text-align:right;">Quarters<br><span class="tich-caption">C</span></th>
                        <th style="text-align:right;">Total Gross<br><span class="tich-caption">D</span></th>
                        <th style="text-align:right;">Pension (E3)</th>
                        <th style="text-align:right;">AHL<br><span class="tich-caption">F</span></th>
                        <th style="text-align:right;">SHIF<br><span class="tich-caption">G</span></th>
                        <th style="text-align:right;">PRMF<br><span class="tich-caption">H</span></th>
                        <th style="text-align:right;">Interest<br><span class="tich-caption">I</span></th>
                        <th style="text-align:right;">Total Ded.<br><span class="tich-caption">J</span></th>
                        <th style="text-align:right;">Chargeable<br><span class="tich-caption">K</span></th>
                        <th style="text-align:right;">Tax Charged<br><span class="tich-caption">L</span></th>
                        <th style="text-align:right;">Relief<br><span class="tich-caption">M</span></th>
                        <th style="text-align:right;">Ins. Relief<br><span class="tich-caption">N</span></th>
                        <th style="text-align:right;">PAYE<br><span class="tich-caption">O</span></th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $months = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
                                   7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
                        $totals = array_fill_keys(['basic_salary','benefits_non_cash','value_of_quarters','total_gross',
                            'pension_e3','ahl','shif','prmf','owner_occupied_interest','total_deductions',
                            'chargeable_pay','tax_charged','personal_relief','insurance_relief','paye'], 0);
                    @endphp
                    @foreach ($months as $m => $name)
                        @php $d = $monthlyData[$m] ?? null; @endphp
                        <tr>
                            <td>{{ $name }}</td>
                            <td style="text-align:right;">{{ $d ? number_format($d['basic_salary']) : '-' }}</td>
                            <td style="text-align:right;">{{ $d ? number_format($d['benefits_non_cash']) : '-' }}</td>
                            <td style="text-align:right;">{{ $d ? number_format($d['value_of_quarters']) : '-' }}</td>
                            <td style="text-align:right;">{{ $d ? number_format($d['total_gross']) : '-' }}</td>
                            <td style="text-align:right;">{{ $d ? number_format($d['pension_e3']) : '-' }}</td>
                            <td style="text-align:right;">{{ $d ? number_format($d['ahl']) : '-' }}</td>
                            <td style="text-align:right;">{{ $d ? number_format($d['shif']) : '-' }}</td>
                            <td style="text-align:right;">{{ $d ? number_format($d['prmf']) : '-' }}</td>
                            <td style="text-align:right;">{{ $d ? number_format($d['owner_occupied_interest']) : '-' }}</td>
                            <td style="text-align:right;">{{ $d ? number_format($d['total_deductions']) : '-' }}</td>
                            <td style="text-align:right;">{{ $d ? number_format($d['chargeable_pay']) : '-' }}</td>
                            <td style="text-align:right;">{{ $d ? number_format($d['tax_charged']) : '-' }}</td>
                            <td style="text-align:right;">{{ $d ? number_format($d['personal_relief']) : '-' }}</td>
                            <td style="text-align:right;">{{ $d ? number_format($d['insurance_relief']) : '-' }}</td>
                            <td style="text-align:right;font-weight:600;">{{ $d ? number_format($d['paye']) : '-' }}</td>
                        </tr>
                        @if ($d)
                            @php
                                foreach ($totals as $key => &$val) { $val += $d[$key]; }
                                unset($val);
                            @endphp
                        @endif
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="font-weight:700;border-top:2px solid var(--tich-neutral-border);">
                        <td>TOTAL</td>
                        <td style="text-align:right;">{{ number_format($totals['basic_salary']) }}</td>
                        <td style="text-align:right;">{{ number_format($totals['benefits_non_cash']) }}</td>
                        <td style="text-align:right;">{{ number_format($totals['value_of_quarters']) }}</td>
                        <td style="text-align:right;">{{ number_format($totals['total_gross']) }}</td>
                        <td style="text-align:right;">{{ number_format($totals['pension_e3']) }}</td>
                        <td style="text-align:right;">{{ number_format($totals['ahl']) }}</td>
                        <td style="text-align:right;">{{ number_format($totals['shif']) }}</td>
                        <td style="text-align:right;">{{ number_format($totals['prmf']) }}</td>
                        <td style="text-align:right;">{{ number_format($totals['owner_occupied_interest']) }}</td>
                        <td style="text-align:right;">{{ number_format($totals['total_deductions']) }}</td>
                        <td style="text-align:right;">{{ number_format($totals['chargeable_pay']) }}</td>
                        <td style="text-align:right;">{{ number_format($totals['tax_charged']) }}</td>
                        <td style="text-align:right;">{{ number_format($totals['personal_relief']) }}</td>
                        <td style="text-align:right;">{{ number_format($totals['insurance_relief']) }}</td>
                        <td style="text-align:right;">{{ number_format($totals['paye']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--tich-neutral-border);">
            <div>
                <p class="tich-caption">Total Chargeable Pay (Col. K)</p>
                <p class="tich-text" style="font-size:1.125rem;font-weight:700;">KES {{ number_format($totals['chargeable_pay']) }}</p>
            </div>
            <div>
                <p class="tich-caption">Total PAYE Tax (Col. O)</p>
                <p class="tich-text" style="font-size:1.125rem;font-weight:700;">KES {{ number_format($totals['paye']) }}</p>
            </div>
        </div>
    </div>
@endsection

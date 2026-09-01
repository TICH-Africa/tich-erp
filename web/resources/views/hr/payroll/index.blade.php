@extends('layouts.hr')

@section('title', 'Payroll')

@section('hr-content')
    @include('partials.financial-privacy')

    @php
        $deductionAmount = function (?array $breakdown, string $code): ?float {
            if (! $breakdown) {
                return null;
            }

            foreach ($breakdown['deductions'] as $row) {
                if (($row['code'] ?? '') === $code) {
                    return (float) $row['amount'];
                }
            }

            return null;
        };
    @endphp

    <x-page-toolbar title="Payroll" meta="Monthly salary breakdown for all staff">
        <x-slot:actions>
            <a href="{{ route('hr.p9-forms.index') }}" class="tich-btn tich-btn-secondary">P9 Forms</a>
            <a href="{{ route('hr.payroll.runs.index') }}" class="tich-btn tich-btn-primary">Payroll runs</a>
            <a href="{{ route('hr.payroll.settings') }}" class="tich-btn tich-btn-secondary">Tax bands &amp; rates</a>
        </x-slot:actions>
        <x-slot:filters>
            <form method="GET" action="{{ route('hr.payroll.index') }}" class="tich-page-toolbar__filters-form">
                @include('partials.search-field', ['placeholder' => 'Name, employee no, email...', 'value' => request('search')])
                <select id="status" name="status" class="tich-input tich-input--compact">
                    <option value="">All statuses</option>
                    @foreach (['active', 'onboarding', 'on_leave', 'suspended', 'terminated', 'resigned'] as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </form>
        </x-slot:filters>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap" style="overflow-x: auto;">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Employee No.</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Job title</th>
                        <th>Status</th>
                        <th>Payroll</th>
                        <th data-financial-col="gross">Consolidated Gross Pay (KES)</th>
                        <th data-financial-col="paye">PAYE</th>
                        <th data-financial-col="wht">WHT</th>
                        <th data-financial-col="nssf">NSSF</th>
                        <th data-financial-col="sha">SHA/SHIF</th>
                        <th data-financial-col="ahl">AHL</th>
                        <th data-financial-col="deductions">Total deductions</th>
                        <th data-financial-col="net">Net (KES)</th>
                        <th data-financial-col="employer">Employer cost</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php
                            $member = $row['staff'];
                            $breakdown = $row['breakdown'];
                        @endphp
                        <tr>
                            <td>{{ $member->employee_number }}</td>
                            <td>
                                <strong>{{ $member->fullName() }}</strong>
                                <p class="tich-caption">{{ $member->organisation_email }}</p>
                            </td>
                            <td>{{ $member->department->dept_name ?? '-' }}</td>
                            <td>{{ $member->job_title ?: '-' }}</td>
                            <td class="tich-caption">{{ ucfirst(str_replace('_', ' ', $member->employment_status)) }}</td>
                            @if ($breakdown)
                                <td class="tich-caption">
                                    {{ $member->usesWithholdingPayroll() ? 'Withholding' : 'Employee' }}
                                    @if ($member->usesWithholdingPayroll())
                                        <span class="tich-caption">({{ rtrim(rtrim(number_format($withholdingRate, 2), '0'), '.') }}%)</span>
                                    @endif
                                </td>
                                <td data-financial-col="gross"><span class="tich-financial-cell">{{ number_format($breakdown['gross_salary'], 2) }}</span></td>
                                <td data-financial-col="paye"><span class="tich-financial-cell">{{ $member->usesWithholdingPayroll() ? '-' : number_format($deductionAmount($breakdown, 'paye') ?? 0, 2) }}</span></td>
                                <td data-financial-col="wht"><span class="tich-financial-cell">{{ $member->usesWithholdingPayroll() ? number_format($deductionAmount($breakdown, 'withholding_tax') ?? 0, 2) : '-' }}</span></td>
                                <td data-financial-col="nssf"><span class="tich-financial-cell">{{ $member->usesWithholdingPayroll() ? '-' : number_format($deductionAmount($breakdown, 'nssf') ?? 0, 2) }}</span></td>
                                <td data-financial-col="sha"><span class="tich-financial-cell">{{ $member->usesWithholdingPayroll() ? '-' : number_format($deductionAmount($breakdown, 'sha') ?? 0, 2) }}</span></td>
                                <td data-financial-col="ahl"><span class="tich-financial-cell">{{ $member->usesWithholdingPayroll() ? '-' : number_format($deductionAmount($breakdown, 'ahl') ?? 0, 2) }}</span></td>
                                <td data-financial-col="deductions"><span class="tich-financial-cell">{{ number_format($breakdown['total_deductions'], 2) }}</span></td>
                                <td data-financial-col="net"><strong><span class="tich-financial-cell">{{ number_format($breakdown['net_salary'], 2) }}</span></strong></td>
                                <td data-financial-col="employer"><span class="tich-financial-cell">{{ number_format($breakdown['total_employer_cost'], 2) }}</span></td>
                                <td style="white-space: nowrap;">
                                    <button
                                        type="button"
                                        class="tich-btn tich-btn-ghost"
                                        data-payslip-preview-id="{{ $member->id }}"
                                        title="Preview payslip"
                                    >Preview</button>
                                    <a href="{{ route('hr.payroll.report', ['staff_id' => $member->id]) }}" class="tich-btn tich-btn-ghost" target="_blank" rel="noopener" title="Open payslip externally">Open</a>
                                    <a href="{{ route('hr.payroll.report.pdf', ['staff_id' => $member->id]) }}" class="tich-btn tich-btn-ghost" title="Download payslip">Download</a>
                                </td>
                            @else
                                <td colspan="9" class="tich-caption">No consolidated gross pay set</td>
                                <td></td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="15" class="tich-table-empty">No staff found.</td></tr>
                    @endforelse
                </tbody>
                @if ($rows->contains(fn ($row) => $row['breakdown'] !== null))
                    <tfoot>
                        <tr>
                            <td colspan="6"><strong>Totals</strong></td>
                            <td data-financial-col="gross"><strong><span class="tich-financial-cell">{{ number_format($totals['gross_salary'], 2) }}</span></strong></td>
                            <td data-financial-col="paye"><strong><span class="tich-financial-cell">{{ number_format($totals['paye'], 2) }}</span></strong></td>
                            <td data-financial-col="wht"><strong><span class="tich-financial-cell">{{ number_format($totals['wht'], 2) }}</span></strong></td>
                            <td data-financial-col="nssf"><strong><span class="tich-financial-cell">{{ number_format($totals['nssf'], 2) }}</span></strong></td>
                            <td data-financial-col="sha"><strong><span class="tich-financial-cell">{{ number_format($totals['sha'], 2) }}</span></strong></td>
                            <td data-financial-col="ahl"><strong><span class="tich-financial-cell">{{ number_format($totals['ahl'], 2) }}</span></strong></td>
                            <td data-financial-col="deductions"><strong><span class="tich-financial-cell">{{ number_format($totals['total_deductions'], 2) }}</span></strong></td>
                            <td data-financial-col="net"><strong><span class="tich-financial-cell">{{ number_format($totals['net_salary'], 2) }}</span></strong></td>
                            <td data-financial-col="employer"><strong><span class="tich-financial-cell">{{ number_format($totals['employer_cost'], 2) }}</span></strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    @include('hr.partials.payroll-payslip-viewer', [
        'payslipPayload' => $payslipPayload,
        'viewerId' => 'hr-payroll-payslip-viewer',
    ])
@endsection

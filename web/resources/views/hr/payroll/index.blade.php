@extends('layouts.hr')

@section('title', 'Payroll')

@section('hr-content')
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

    <div class="tich-mb-8">
        <div class="tich-flex tich-flex--between tich-flex--start">
            <div>
                <h1 class="tich-h1">Payroll</h1>
                <p class="tich-text tich-mt-2">Monthly salary breakdown for all staff — gross pay, statutory deductions, and net pay per configured KRA bands.</p>
            </div>
            <a href="{{ route('hr.payroll.settings') }}" class="tich-btn tich-btn-secondary">Tax bands &amp; rates</a>
        </div>
    </div>

    <div class="tich-card tich-mb-8">
        <form method="GET" action="{{ route('hr.payroll.index') }}" class="tich-grid tich-grid--3">
            <div>
                <label for="search" class="tich-label">Search</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Name, employee no, email..." class="tich-input">
            </div>
            <div>
                <label for="status" class="tich-label">Status</label>
                <select id="status" name="status" class="tich-input">
                    <option value="">All statuses</option>
                    @foreach (['active', 'onboarding', 'on_leave', 'suspended', 'terminated', 'resigned'] as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-flex--end">
                <button type="submit" class="tich-btn tich-btn-primary">Filter</button>
            </div>
        </form>
    </div>

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
                        <th>Gross (KES)</th>
                        <th>PAYE</th>
                        <th>NSSF</th>
                        <th>SHA/SHIF</th>
                        <th>AHL</th>
                        <th>Total deductions</th>
                        <th>Net (KES)</th>
                        <th>Employer cost</th>
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
                            <td>{{ $member->department->dept_name ?? '—' }}</td>
                            <td>{{ $member->job_title ?: '—' }}</td>
                            <td class="tich-caption">{{ ucfirst(str_replace('_', ' ', $member->employment_status)) }}</td>
                            @if ($breakdown)
                                <td>{{ number_format($breakdown['gross_salary'], 2) }}</td>
                                <td>{{ number_format($deductionAmount($breakdown, 'paye') ?? 0, 2) }}</td>
                                <td>{{ number_format($deductionAmount($breakdown, 'nssf') ?? 0, 2) }}</td>
                                <td>{{ number_format($deductionAmount($breakdown, 'sha') ?? 0, 2) }}</td>
                                <td>{{ number_format($deductionAmount($breakdown, 'ahl') ?? 0, 2) }}</td>
                                <td>{{ number_format($breakdown['total_deductions'], 2) }}</td>
                                <td><strong>{{ number_format($breakdown['net_salary'], 2) }}</strong></td>
                                <td>{{ number_format($breakdown['total_employer_cost'], 2) }}</td>
                                <td style="white-space: nowrap;">
                                    <a href="{{ route('hr.payroll.report', ['staff_id' => $member->id]) }}" class="tich-btn tich-btn-ghost" target="_blank" title="Preview payslip">View</a>
                                    <a href="{{ route('hr.payroll.report.pdf', ['staff_id' => $member->id]) }}" class="tich-btn tich-btn-ghost" title="Download PDF">PDF</a>
                                </td>
                            @else
                                <td colspan="8" class="tich-caption">No gross salary set</td>
                                <td></td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="14" class="tich-table-empty">No staff found.</td></tr>
                    @endforelse
                </tbody>
                @if ($rows->contains(fn ($row) => $row['breakdown'] !== null))
                    <tfoot>
                        <tr>
                            <td colspan="5"><strong>Totals</strong></td>
                            <td><strong>{{ number_format($totals['gross_salary'], 2) }}</strong></td>
                            <td><strong>{{ number_format($totals['paye'], 2) }}</strong></td>
                            <td><strong>{{ number_format($totals['nssf'], 2) }}</strong></td>
                            <td><strong>{{ number_format($totals['sha'], 2) }}</strong></td>
                            <td><strong>{{ number_format($totals['ahl'], 2) }}</strong></td>
                            <td><strong>{{ number_format($totals['total_deductions'], 2) }}</strong></td>
                            <td><strong>{{ number_format($totals['net_salary'], 2) }}</strong></td>
                            <td><strong>{{ number_format($totals['employer_cost'], 2) }}</strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection

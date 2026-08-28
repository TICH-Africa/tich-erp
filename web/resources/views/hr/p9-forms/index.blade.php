@extends('layouts.hr')

@section('title', 'P9 Forms')

@section('hr-content')
    <x-page-toolbar title="P9 Tax Deduction Cards" meta="Generate and download KRA P9A forms for staff">
        <x-slot:actions>
            <form method="GET" style="display:inline-flex;gap:0.5rem;align-items:center;">
                <label class="tich-caption" for="year">Tax year:</label>
                <select name="year" id="year" class="tich-input tich-input--compact" onchange="this.form.submit()">
                    @foreach ($availableYears as $y)
                        <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
                    @endforeach
                </select>
            </form>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Employee No.</th>
                        <th>Name</th>
                        <th>KRA PIN</th>
                        <th>Department</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($staff as $employee)
                        <tr>
                            <td><strong>{{ $employee->employee_number }}</strong></td>
                            <td>{{ $employee->fullName() }}</td>
                            <td class="tich-caption">{{ $employee->kra_pin ?? 'Not set' }}</td>
                            <td class="tich-caption">{{ $employee->department?->dept_name ?? '-' }}</td>
                            <td style="display:flex;gap:0.25rem;">
                                <a href="{{ route('hr.p9-forms.show', ['staff' => $employee, 'year' => $year]) }}"
                                   class="tich-btn tich-btn-ghost">View</a>
                                <a href="{{ route('hr.p9-forms.download', ['staff' => $employee, 'year' => $year]) }}"
                                   class="tich-btn tich-btn-ghost">Download</a>
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', [
                            'colspan' => 5,
                            'title' => 'No payroll data for ' . $year,
                            'description' => 'Create a payroll run for this tax year first. Your current runs may be under a different year - use the Tax year filter above.',
                            'icon' => 'inbox',
                        ])
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="tich-card tich-mt-6">
        <h3 class="tich-h3 tich-mb-4">About P9A Forms</h3>
        <ul class="tich-text" style="padding-left:1.25rem;line-height:1.8;">
            <li>P9A (Appendix P9A) is the annual Tax Deduction Card required by KRA for all liable employees.</li>
            <li>Each form shows monthly breakdowns of: Basic Salary, Benefits, Gross Pay, Pension/NSSF, AHL, SHIF, PRMF, Chargeable Pay, PAYE, and Reliefs.</li>
            <li>Personal Relief: KES 2,400/month (28,800/year). Insurance Relief: 15% of premium, max KES 5,000/month.</li>
            <li>Generated from approved payroll runs. Ensure all months are processed before issuing to employees.</li>
        </ul>
    </div>
@endsection

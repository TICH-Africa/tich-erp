@extends('layouts.finance')

@section('title', 'Payslip Calculator')

@section('finance-content')
    <x-page-toolbar title="Payslip calculator" meta="Estimate PAYE, statutory deductions, and net pay from gross or net amounts">
        <x-slot:actions>
            <a href="{{ route('finance.employee.payroll.index') }}" class="tich-btn tich-btn-secondary">&larr; Back to payroll</a>
        </x-slot:actions>
    </x-page-toolbar>

    <article class="tich-card tich-mt-8">
        <form method="GET" action="{{ route('finance.employee.payroll.report') }}" class="tich-form-grid tich-form-grid--2">
            <div class="tich-form-group">
                <label class="tich-label">Staff member</label>
                <select name="staff_id" class="tich-input">
                    <option value="">- or calculate manually below -</option>
                    @php
                        $staffList = \App\Models\Staff::query()
                            ->orderBy('surname')
                            ->orderBy('first_name')
                            ->get(['id', 'first_name', 'surname', 'employee_number', 'gross_monthly_salary']);
                    @endphp
                    @foreach ($staffList as $member)
                        <option value="{{ $member->id }}" {{ request('staff_id') == $member->id ? 'selected' : '' }}>
                            {{ $member->fullName() }} ({{ $member->employee_number }}) - KES {{ number_format((float) $member->gross_monthly_salary, 2) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="tich-form-group">
                <label class="tich-label">Or gross/net amount (KES)</label>
                <input type="number" name="amount" class="tich-input" value="{{ request('amount') }}" min="0" step="0.01" placeholder="e.g. 50000">
            </div>

            <div class="tich-form-group">
                <label class="tich-label">Mode</label>
                <select name="mode" class="tich-input">
                    <option value="gross" {{ request('mode', 'gross') === 'gross' ? 'selected' : '' }}>Calculate from gross</option>
                    <option value="net" {{ request('mode') === 'net' ? 'selected' : '' }}>Calculate from net (gross-up)</option>
                </select>
            </div>

            <div class="tich-form-group">
                <label class="tich-label">Allowances (KES, optional)</label>
                <input type="number" name="allowances" class="tich-input" value="{{ request('allowances') }}" min="0" step="0.01" placeholder="e.g. 5000">
            </div>

            <div class="tich-form-group">
                <label class="tich-label">Other deductions (KES, optional)</label>
                <input type="number" name="other_deductions" class="tich-input" value="{{ request('other_deductions') }}" min="0" step="0.01" placeholder="e.g. 2000">
            </div>

            <div class="tich-form-group" style="grid-column: 1 / -1;">
                <button type="submit" class="tich-btn tich-btn-primary">Calculate</button>
            </div>
        </form>
    </article>
@endsection

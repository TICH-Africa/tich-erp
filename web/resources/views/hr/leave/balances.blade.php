@extends('layouts.hr')

@section('title', 'Edit leave balances')

@section('hr-content')
    <x-page-toolbar
        title="Leave balances"
        :meta="$staff->fullName() . ' · ' . $staff->employee_number"
    >
        <x-slot:actions>
            <a href="{{ route('hr.leave.employees') }}" class="tich-btn tich-btn-ghost">Back to employees</a>
            <a href="{{ route('hr.leave.apply.create', ['staff_id' => $staff->id]) }}" class="tich-btn tich-btn-primary">Apply leave</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if (session('success'))
        <div class="tich-alert tich-alert--success tich-mb-4">{{ session('success') }}</div>
    @endif

    @if ($errors->has('leave'))
        <div class="tich-alert tich-alert--error tich-mb-4">{{ $errors->first('leave') }}</div>
    @endif

    <div class="tich-card tich-mb-4">
        <p class="tich-text">
            Adjust entitled and taken days when migrating from manual records. Remaining balance is recalculated automatically
            (entitlement + carry-forward − taken − pending).
        </p>
    </div>

    <div class="uf-form">
    <form method="POST" action="{{ route('hr.leave.balances.update', $staff) }}" class="tich-card tich-table-panel">
        @csrf
        <input type="hidden" name="year" value="{{ $year }}">

        <div class="uf-field tich-mb-4">
            <label for="adjustment_note">Note (optional)</label>
            <input type="text" id="adjustment_note" name="adjustment_note" class="tich-input" maxlength="500"
                value="{{ old('adjustment_note') }}" placeholder="e.g. Opening balance from 2024 manual register">
        </div>

        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Leave type</th>
                        <th>Entitled</th>
                        <th>Carried forward</th>
                        <th>Days taken</th>
                        <th>Pending approval</th>
                        <th>Will remain</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($balances as $row)
                        @php
                            $prefix = 'balances.'.$row->leave_type_id;
                            $entitled = old($prefix.'.entitled_days', $row->entitled_days);
                            $carried = old($prefix.'.carried_forward_days', $row->carried_forward_days);
                            $taken = old($prefix.'.days_taken', $row->days_taken);
                            $pending = old($prefix.'.days_pending', $row->days_pending);
                            $remain = max(0, (float) $entitled + (float) $carried - (float) $taken - (float) $pending);
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $row->leave_type_name }}</strong>
                                <p class="tich-caption">{{ $row->leave_code }} · {{ $year }}</p>
                            </td>
                            <td>
                                <input type="number" step="0.5" min="0" max="365" class="tich-input tich-input--compact"
                                    name="balances[{{ $row->leave_type_id }}][entitled_days]" value="{{ $entitled }}" required>
                            </td>
                            <td>
                                <input type="number" step="0.5" min="0" max="365" class="tich-input tich-input--compact"
                                    name="balances[{{ $row->leave_type_id }}][carried_forward_days]" value="{{ $carried }}">
                            </td>
                            <td>
                                <input type="number" step="0.5" min="0" max="365" class="tich-input tich-input--compact"
                                    name="balances[{{ $row->leave_type_id }}][days_taken]" value="{{ $taken }}" required>
                            </td>
                            <td>
                                <input type="number" step="0.5" min="0" max="365" class="tich-input tich-input--compact"
                                    name="balances[{{ $row->leave_type_id }}][days_pending]" value="{{ $pending }}">
                            </td>
                            <td><strong>{{ number_format($remain, 1) }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="tich-mt-4">
            <button type="submit" class="tich-btn tich-btn-primary">Save balances</button>
        </div>
    </form>
    </div>
@endsection

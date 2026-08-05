@extends('layouts.hr')

@section('title', 'Leave overview')

@section('hr-content')
    <x-page-toolbar
        title="Leave overview"
        :meta="$onLeaveCount > 0 ? $onLeaveCount . ' on leave today' : 'No employees on leave today'"
    >
        <x-slot:filters>
            <form method="GET" action="{{ route('hr.leave.overview') }}" class="tich-page-toolbar__filters-form">
                @include('partials.search-field', [
                    'placeholder' => 'Name or employee number',
                    'value' => request('search'),
                ])
            </form>
        </x-slot:filters>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Leave type</th>
                        <th>Reason</th>
                        <th>Leave period</th>
                        <th>Accrued days</th>
                        <th>Days taken</th>
                        <th>Days balance</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            <td>
                                <strong>{{ $record->staff->fullName() }}</strong>
                                <p class="tich-caption">{{ $record->staff->employee_number }} · {{ $record->staff->department?->dept_name ?? '—' }}</p>
                            </td>
                            <td>{{ $record->leave_type_name }}</td>
                            <td>{{ $record->reason }}</td>
                            <td>{{ $record->period_label }}</td>
                            <td>{{ $record->accrued_days }}</td>
                            <td>{{ $record->days_taken }}</td>
                            <td>{{ $record->balance_days }}</td>
                            <td>
                                <a href="{{ route('hr.leave.show', $record->leave_request) }}" class="tich-btn tich-btn-ghost">View request</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="tich-text tich-text--secondary">No employees are currently on leave.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

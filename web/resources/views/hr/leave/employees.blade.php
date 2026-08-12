@extends('layouts.hr')

@section('title', 'All employees leave')

@section('hr-content')
    <x-page-toolbar
        title="All employees"
        :meta="$employeeCount . ' employees' . ($onLeaveCount > 0 ? ' · ' . $onLeaveCount . ' on leave' : '')"
    >
        <x-slot:filters>
            <form method="GET" action="{{ route('hr.leave.employees') }}" class="tich-page-toolbar__filters-form">
                @include('partials.search-field', [
                    'placeholder' => 'Name, employee number, or job title',
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
                        <th>Department</th>
                        <th>On leave</th>
                        <th>Current leave</th>
                        <th>Accrued days</th>
                        <th>Days taken</th>
                        <th>Days remaining</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            <td>
                                <strong>{{ $record->staff->fullName() }}</strong>
                                <p class="tich-caption">{{ $record->staff->employee_number }}</p>
                            </td>
                            <td>{{ $record->staff->department?->dept_name ?? '-' }}</td>
                            <td>
                                @if ($record->on_leave)
                                    <span class="tich-leave-status tich-leave-status--on">On leave</span>
                                @else
                                    <span class="tich-leave-status tich-leave-status--off">Not on leave</span>
                                @endif
                            </td>
                            <td>
                                @if ($record->on_leave)
                                    <strong>{{ $record->current_leave_type }}</strong>
                                    <p class="tich-caption">{{ $record->current_leave_period }}</p>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $record->accrued_days }}</td>
                            <td>{{ $record->days_taken }}</td>
                            <td>{{ $record->days_remaining }}</td>
                            <td>
                                <a href="{{ route('hr.staff.show', $record->staff) }}" class="tich-btn tich-btn-ghost">View profile</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="tich-text tich-text--secondary">No employees found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

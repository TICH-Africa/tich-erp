@extends('layouts.hr')

@section('title', 'All employees leave')

@section('hr-content')
    <x-page-toolbar
        title="All employees"
        :meta="$employeeCount . ' employees' . ($onLeaveCount > 0 ? ' · ' . $onLeaveCount . ' on leave' : '')"
    >
        <x-slot:actions>
            @can('hr.manage_leave')
                <a href="{{ route('hr.leave.apply.create') }}" class="tich-btn tich-btn-primary">Apply for employee</a>
            @endcan
        </x-slot:actions>
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
                        <th>Annual entitled</th>
                        <th>Annual taken</th>
                        <th>Annual left</th>
                        <th>All types taken</th>
                        <th>All types left</th>
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
                            <td>{{ $record->annual_entitled }}</td>
                            <td>{{ $record->annual_taken }}</td>
                            <td>
                                <strong>{{ $record->annual_remaining }}</strong>
                                @if ($record->annual_pending > 0)
                                    <p class="tich-caption">{{ $record->annual_pending }} pending</p>
                                @endif
                            </td>
                            <td>{{ $record->days_taken }}</td>
                            <td>{{ $record->days_remaining }}</td>
                            <td>
                                @can('hr.manage_leave')
                                    <a href="{{ route('hr.leave.balances.edit', $record->staff) }}" class="tich-btn tich-btn-ghost">Edit balances</a>
                                    <a href="{{ route('hr.leave.apply.create', ['staff_id' => $record->staff->id]) }}" class="tich-btn tich-btn-ghost">Apply leave</a>
                                @else
                                    <a href="{{ route('hr.staff.show', $record->staff) }}" class="tich-btn tich-btn-ghost">Profile</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="tich-text tich-text--secondary">No employees found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

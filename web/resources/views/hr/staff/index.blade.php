@extends('layouts.hr')

@section('title', 'Staff Directory')

@section('hr-content')
    <x-page-toolbar title="Staff Directory" meta="Employee profiles and lifecycle records">
        <x-slot:actions>
            <a href="{{ route('hr.staff.create') }}" class="tich-btn tich-btn-primary">+ Add Staff</a>
        </x-slot:actions>
        <x-slot:filters>
            <form method="GET" action="{{ route('hr.staff.index') }}" class="tich-page-toolbar__filters-form">
                @include('partials.search-field', ['placeholder' => 'Name, employee no, email...', 'value' => request('search')])
                <select id="status" name="status" class="tich-input tich-input--compact">
                    <option value="">All statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="onboarding" {{ request('status') === 'onboarding' ? 'selected' : '' }}>Onboarding</option>
                    <option value="on_leave" {{ request('status') === 'on_leave' ? 'selected' : '' }}>On Leave</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="terminated" {{ request('status') === 'terminated' ? 'selected' : '' }}>Terminated</option>
                    <option value="resigned" {{ request('status') === 'resigned' ? 'selected' : '' }}>Resigned</option>
                </select>
                <select id="department_id" name="department_id" class="tich-input tich-input--compact">
                    <option value="">All departments</option>
                    @foreach ($departments ?? [] as $department)
                        <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>
                            {{ $department->dept_name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </x-slot:filters>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Employee No.</th>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Job Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Profile</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($staff as $member)
                        <tr>
                            <td>{{ $member->employee_number }}</td>
                            <td>
                                @include('hr.staff.partials.table-avatar', ['member' => $member])
                            </td>
                            <td>
                                <strong>{{ $member->fullName() }}</strong>
                                <p class="tich-caption">{{ $member->organisation_email }}</p>
                                @if ($member->primary_email && $member->primary_email !== $member->organisation_email)
                                    <p class="tich-caption">Personal: {{ $member->primary_email }}</p>
                                @endif
                            </td>
                            <td>{{ $member->department->dept_name ?? '-' }}</td>
                            <td>{{ $member->job_title }}</td>
                            <td class="tich-caption">{{ config('tich-payroll.employment_categories.'.$member->employment_category, ucfirst(str_replace('_', ' ', $member->employment_category))) }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ $member->employment_status === 'active' ? 'success' : 'warning' }}">
                                    {{ ucfirst($member->employment_status) }}
                                </span>
                            </td>
                            <td>
                                @if ($member->is_profile_locked)
                                    <span class="tich-caption">Locked</span>
                                @else
                                    <span class="tich-caption">Editable</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('hr.staff.show', $member) }}" class="tich-btn tich-btn-ghost">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="tich-table-empty">No staff records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($staff->hasPages())
            <div class="tich-mt-6">
                {{ $staff->links() }}
            </div>
        @endif
    </div>
@endsection

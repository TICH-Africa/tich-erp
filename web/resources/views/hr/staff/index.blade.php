@extends('layouts.hr')

@section('title', 'Staff Directory')

@section('hr-content')
    <div class="tich-mb-8">
        <div class="tich-flex tich-flex--between tich-flex--start">
            <div>
                <h1 class="tich-h1">Staff Directory</h1>
                <p class="tich-text tich-mt-2">Manage employee master profiles and lifecycle records.</p>
            </div>
            <a href="{{ route('hr.staff.create') }}" class="tich-btn tich-btn-primary">+ Add Staff</a>
        </div>
    </div>

    <div class="tich-card tich-mb-8">
        <form method="GET" action="{{ route('hr.staff.index') }}" class="tich-grid tich-grid--4">
            <div>
                <label for="search" class="tich-label">Search</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Name, employee no, email..." class="tich-input">
            </div>
            <div>
                <label for="status" class="tich-label">Status</label>
                <select id="status" name="status" class="tich-input">
                    <option value="">All statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="onboarding" {{ request('status') === 'onboarding' ? 'selected' : '' }}>Onboarding</option>
                    <option value="on_leave" {{ request('status') === 'on_leave' ? 'selected' : '' }}>On Leave</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="terminated" {{ request('status') === 'terminated' ? 'selected' : '' }}>Terminated</option>
                    <option value="resigned" {{ request('status') === 'resigned' ? 'selected' : '' }}>Resigned</option>
                </select>
            </div>
            <div>
                <label for="department_id" class="tich-label">Department</label>
                <select id="department_id" name="department_id" class="tich-input">
                    <option value="">All departments</option>
                    @foreach ($departments ?? [] as $department)
                        <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>
                            {{ $department->dept_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="tich-flex--end">
                <button type="submit" class="tich-btn tich-btn-primary">Filter</button>
            </div>
        </form>
    </div>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Employee No.</th>
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
                                <strong>{{ $member->fullName() }}</strong>
                                <p class="tich-caption">{{ $member->organisation_email }}</p>
                                @if ($member->primary_email && $member->primary_email !== $member->organisation_email)
                                    <p class="tich-caption">Personal: {{ $member->primary_email }}</p>
                                @endif
                            </td>
                            <td>{{ $member->department->dept_name ?? '—' }}</td>
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
                            <td colspan="8" class="tich-table-empty">No staff records found.</td>
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

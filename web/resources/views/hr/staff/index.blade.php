@extends('layouts.hr')

@section('title', 'Staff Directory')

@section('hr-content')
    <x-page-toolbar title="Staff Directory" meta="Employee profiles and lifecycle records">
        <x-slot:actions>
            <a href="{{ route('hr.staff.create') }}" class="tich-btn tich-btn-primary">+ Add Staff</a>
        </x-slot:actions>
        <x-slot:filters>
            <form method="GET" action="{{ route('hr.staff.index') }}" class="tich-page-toolbar__filters-form" data-live-filter>
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
                        <th>Departments</th>
                        <th>Job Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($staff as $member)
                        @php
                            $memberDepartmentIds = collect([$member->department_id])
                                ->merge($member->user?->roles->pluck('pivot.department_id') ?? [])
                                ->filter()
                                ->map(fn ($id) => (int) $id)
                                ->unique()
                                ->values();
                            $memberDepartments = $memberDepartmentIds
                                ->map(fn ($id) => $departmentsById[$id] ?? null)
                                ->filter()
                                ->unique()
                                ->sort()
                                ->values();
                        @endphp
                        <tr>
                            <td>{{ $member->employee_number }}</td>
                            <td>
                                @include('hr.staff.partials.table-avatar', ['member' => $member])
                            </td>
                            <td>
                                <strong>{{ $member->fullName() }}</strong>
                                @if ($member->organisation_email)
                                    <p class="tich-caption">{{ $member->organisation_email }}</p>
                                @endif
                                @if ($member->primary_email)
                                    <p class="tich-caption">{{ $member->organisation_email ? 'Personal: ' : '' }}{{ $member->primary_email }}</p>
                                @endif
                                @if (strcasecmp((string) $member->first_name, 'Pending') === 0 && strcasecmp((string) $member->surname, 'Invitee') === 0)
                                    <p class="tich-caption" style="color:#b45309;">Awaiting employee profile</p>
                                @elseif ($member->employment_status === 'onboarding')
                                    <p class="tich-caption">Status: onboarding</p>
                                @endif
                            </td>
                            <td>
                                @forelse ($memberDepartments as $departmentName)
                                    <span class="tich-badge tich-badge--sm tich-staff-dept-badge">{{ $departmentName }}</span>
                                @empty
                                    <span class="tich-caption">-</span>
                                @endforelse
                            </td>
                            <td>{{ $member->job_title }}</td>
                            <td class="tich-caption">{{ config('tich-payroll.employment_categories.'.$member->employment_category, ucfirst(str_replace('_', ' ', $member->employment_category))) }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ $member->employment_status === 'active' ? 'success' : 'warning' }}">
                                    {{ ucfirst($member->employment_status) }}
                                </span>
                            </td>
                            <td>
                                <div class="tich-staff-row-actions">
                                    @if (! $member->user_id && $member->primary_email)
                                        <form method="POST" action="{{ route('hr.staff.invite', $member) }}">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="tich-squircle-btn"
                                                title="Invite"
                                                aria-label="Invite"
                                            >
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                                    <circle cx="9" cy="7" r="4"/>
                                                    <line x1="19" y1="8" x2="19" y2="14"/>
                                                    <line x1="22" y1="11" x2="16" y2="11"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @elseif ($member->user_id)
                                        <span
                                            class="tich-staff-account-icon"
                                            title="Has account"
                                            aria-label="Has account"
                                        >
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                                <circle cx="12" cy="7" r="4"/>
                                            </svg>
                                        </span>
                                    @endif
                                    <a href="{{ route('hr.staff.show', $member) }}" class="tich-btn tich-btn-ghost">View</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 8, 'title' => 'No staff records found', 'icon' => 'inbox'])
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

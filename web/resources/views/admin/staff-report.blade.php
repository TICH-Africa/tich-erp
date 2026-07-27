@extends('layouts.admin')

@section('title', 'Staff report')

@section('admin-content')
    <div class="tich-mb-8">
        <h1 class="tich-h1" style="font-size: 2rem;">Staff report</h1>
        <p class="tich-text tich-mt-2">View all teaching staff and their unit allocations.</p>
    </div>

    <div class="tich-card tich-mb-8" style="overflow-x:auto;">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Staff</th>
                    <th>Department</th>
                    <th>Role</th>
                    <th>Units / Programs</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($staff as $member)
                    @php
                        $deptName = $member->department?->dept_name ?? 'Unassigned';
                        $roleName = $member->user?->roles->first()?->role_name ?? 'No role';
                        $unitAllocations = $member->unitAllocations;
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $member->fullName() }}</strong><br>
                            <span class="tich-caption">{{ $member->employee_number }}</span>
                        </td>
                        <td>{{ $deptName }}</td>
                        <td>{{ $roleName }}</td>
                        <td>
                            @if ($unitAllocations->isNotEmpty())
                                <ul class="tich-mb-2" style="margin:0; padding-left:1.25rem;">
                                    @foreach ($unitAllocations as $alloc)
                                        <li>
                                            {{ $alloc->unit->unit_code ?? 'Unit' }}: {{ $alloc->unit->unit_name ?? '' }}
                                            @if ($alloc->unit?->program)
                                                <span class="tich-caption">({{ $alloc->unit->program->program_name }}, {{ $alloc->unit->program->department->dept_name ?? '' }})</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                                <a href="{{ route('admin.users.edit', $member->user) }}" class="tich-link tich-caption">Manage</a>
                            @else
                                No units assigned
                                <a href="{{ route('admin.users.edit', $member->user) }}" class="tich-link tich-caption">Assign</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="tich-text" style="padding:2rem; text-align:center;">No teaching staff found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
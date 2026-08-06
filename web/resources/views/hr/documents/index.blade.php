@extends('layouts.hr')

@section('title', 'Staff Documents')

@section('hr-content')
    <x-page-toolbar title="Staff Documents" meta="Documents for all staff members" />

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Employee No.</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Job Title</th>
                        <th>Documents</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($staff as $member)
                        <tr>
                            <td>{{ $member->employee_number }}</td>
                            <td>
                                <strong>{{ $member->fullName() }}</strong>
                            </td>
                            <td class="tich-caption">{{ $member->department->dept_name ?? '—' }}</td>
                            <td class="tich-caption">{{ $member->job_title }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ $member->documents_count > 0 ? 'success' : 'warning' }}">
                                    {{ $member->documents_count }} uploaded
                                </span>
                                @php
                                    $pendingCount = $member->documents->where('status', 'pending')->count();
                                    $approvedCount = $member->documents->where('status', 'approved')->count();
                                    $rejectedCount = $member->documents->where('status', 'rejected')->count();
                                @endphp
                                @if ($pendingCount > 0)
                                    <span class="tich-badge tich-badge--warning tich-ml-1">{{ $pendingCount }} pending</span>
                                @endif
                                @if ($approvedCount > 0)
                                    <span class="tich-badge tich-badge--success tich-ml-1">{{ $approvedCount }} approved</span>
                                @endif
                                @if ($rejectedCount > 0)
                                    <span class="tich-badge tich-badge--danger tich-ml-1">{{ $rejectedCount }} rejected</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('hr.documents.show', $member) }}" class="tich-btn tich-btn-ghost">View Documents</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="tich-table-empty">No staff records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

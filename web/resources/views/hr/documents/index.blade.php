@extends('layouts.hr')

@section('title', 'Staff Documents')

@section('hr-content')
    <div class="tich-mb-8">
        <h1 class="tich-h1">Staff Documents</h1>
        <p class="tich-text tich-mt-2">View and manage documents for all staff members.</p>
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

@extends('layouts.approval')

@section('approval-content')
    <h1 class="tich-h1" style="font-size: 2rem;">Applications</h1>
    <p class="tich-text tich-mb-6">
        Review onboarding submissions and identify the department handling each application.
    </p>

    <form method="GET" action="{{ route('admissions.applications.index') }}" class="tich-card tich-mb-6" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: end;">
        <div class="tich-form-group" style="margin: 0; min-width: 12rem;">
            <label class="tich-label">Department</label>
            <select name="department" class="tich-input">
                <option value="">All departments</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected($filters['department'] == $department->id)>
                        {{ $department->dept_name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="tich-form-group" style="margin: 0; min-width: 12rem;">
            <label class="tich-label">Status</label>
            <select name="status" class="tich-input">
                <option value="">All</option>
                <option value="pending" @selected($filters['status'] === 'pending')>Pending review</option>
                <option value="admitted" @selected($filters['status'] === 'admitted')>Admitted</option>
                <option value="rejected" @selected($filters['status'] === 'rejected')>Rejected</option>
            </select>
        </div>
        @if (! empty($filters['program']))
            <input type="hidden" name="program" value="{{ $filters['program'] }}">
        @endif
        <button type="submit" class="tich-btn tich-btn-primary">Filter</button>
        <a href="{{ route('admissions.applications.index') }}" class="tich-link">Clear</a>
    </form>

    <div class="tich-card tich-table-panel">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Application</th>
                    <th>Applicant</th>
                    <th>Programme</th>
                    <th>Handling department</th>
                    <th>Campus</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($applications as $application)
                    <tr>
                        <td>{{ $application->application_number }}</td>
                        <td>
                            {{ $application->fullName() }}<br>
                            <span class="tich-caption">{{ $application->email }}</span>
                        </td>
                        <td>
                            {{ $application->program?->program_name ?? '-' }}<br>
                            <span class="tich-caption">{{ $application->program?->program_code }}</span>
                        </td>
                        <td>
                            <strong>{{ $application->program?->department?->dept_name ?? $application->handlingDepartment?->dept_name ?? 'Unassigned' }}</strong>
                        </td>
                        <td>{{ $application->preferredCampus?->campus_name ?? '-' }}</td>
                        <td>{{ $application->created_at?->format('d M Y') ?? '-' }}</td>
                        <td>@include('admissions.partials.status-badge', ['applicant' => $application])</td>
                        <td>
                            <a href="{{ route('admissions.applications.show', $application->id) }}" class="tich-link">Review</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">No applications match your filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

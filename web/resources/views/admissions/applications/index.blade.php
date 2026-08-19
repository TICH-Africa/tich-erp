@extends('layouts.approval')

@section('approval-content')
    <x-page-toolbar title="Applications" meta="Review onboarding submissions by department">
        <x-slot:filters>
            <form method="GET" action="{{ route('admissions.applications.index') }}" class="tich-page-toolbar__filters-form">
                @include('partials.search-field', ['placeholder' => 'Name, email, application no.', 'value' => request('search')])
                <select name="department" class="tich-input tich-input--compact">
                    <option value="">All departments</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected($filters['department'] == $department->id)>
                            {{ $department->dept_name }}
                        </option>
                    @endforeach
                </select>
                <select name="status" class="tich-input tich-input--compact">
                    <option value="">All</option>
                    <option value="pending" @selected($filters['status'] === 'pending')>Pending review</option>
                    <option value="payment_pending" @selected($filters['status'] === 'payment_pending')>Awaiting payment/finalization</option>
                    <option value="admitted" @selected($filters['status'] === 'admitted')>Admitted</option>
                    <option value="rejected" @selected($filters['status'] === 'rejected')>Rejected</option>
                </select>
                @if (! empty($filters['program']))
                    <input type="hidden" name="program" value="{{ $filters['program'] }}">
                @endif
            </form>
        </x-slot:filters>
    </x-page-toolbar>

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
                    @include('partials.states.table-empty', ['colspan' => 8, 'title' => 'No applications match your filters', 'icon' => 'inbox'])
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

@extends('layouts.academics')

@section('title', 'Application review')

@section('academics-content')
    @php($hub = \App\Support\AcademicsRouteParams::fromRequest(request()))

    <x-page-toolbar title="Application review" meta="Academic qualification review for forwarded applications">
        <x-slot:filters>
            <form method="GET" action="{{ route('departments.academics.applications.index', $hub) }}" class="tich-page-toolbar__filters-form">
                @include('partials.search-field', ['placeholder' => 'Name, email, application no.', 'value' => request('search')])
                <select name="learning_department" class="tich-input tich-input--compact">
                    <option value="">All departments</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->id }}" @selected($filters['department'] == $dept->id)>
                            {{ $dept->dept_name }}
                        </option>
                    @endforeach
                </select>
                <select name="status" class="tich-input tich-input--compact">
                    <option value="pending" @selected($filters['status'] === 'pending')>Awaiting review</option>
                    <option value="payment_pending" @selected($filters['status'] === 'payment_pending')>Approved — awaiting payment</option>
                    <option value="admitted" @selected($filters['status'] === 'admitted')>Admitted</option>
                    <option value="rejected" @selected($filters['status'] === 'rejected')>Rejected</option>
                </select>
                @if (! empty($filters['program']))
                    <input type="hidden" name="program" value="{{ $filters['program'] }}">
                @endif
            </form>
        </x-slot:filters>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mt-8">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Application</th>
                    <th>Applicant</th>
                    <th>Programme</th>
                    <th>Handling department</th>
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
                        <td>{{ $application->created_at?->format('d M Y') ?? '-' }}</td>
                        <td>@include('applications.partials.status-badge', ['applicant' => $application])</td>
                        <td>
                            <a href="{{ route('departments.academics.applications.show', array_merge($hub, ['id' => $application->id])) }}" class="tich-link">Review</a>
                        </td>
                    </tr>
                @empty
                    @include('partials.states.table-empty', ['colspan' => 7, 'title' => 'No applications match your filters', 'icon' => 'inbox'])
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

@extends('layouts.hr')

@section('title', 'Recruitment')

@section('hr-content')
    <div class="tich-mb-8">
        <h1 class="tich-h1">Recruitment Applications</h1>
        <p class="tich-text tich-mt-2">Review and manage job applications.</p>
    </div>

    <div class="tich-card tich-mb-8">
        <form method="GET" action="{{ route('hr.recruitment.index') }}" class="tich-grid tich-grid--4">
            <div>
                <label for="search" class="tich-label">Search</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Name, email, phone..." class="tich-input">
            </div>
            <div>
                <label for="status" class="tich-label">Status</label>
                <select id="status" name="status" class="tich-input">
                    <option value="">All statuses</option>
                    <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Submitted</option>
                    <option value="under_review" {{ request('status') == 'under_review' ? 'selected' : '' }}>Under Review</option>
                    <option value="shortlisted" {{ request('status') == 'shortlisted' ? 'selected' : '' }}>Shortlisted</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="offered" {{ request('status') == 'offered' ? 'selected' : '' }}>Offered</option>
                </select>
            </div>
            <div>
                <label for="vacancy_id" class="tich-label">Vacancy</label>
                <select id="vacancy_id" name="vacancy_id" class="tich-input">
                    <option value="">All vacancies</option>
                    @foreach ($vacancies as $vacancy)
                        <option value="{{ $vacancy->id }}" {{ request('vacancy_id') == $vacancy->id ? 'selected' : '' }}>
                            {{ $vacancy->job_title }}
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
                        <th>Application No.</th>
                        <th>Applicant</th>
                        <th>Vacancy</th>
                        <th>Date Applied</th>
                        <th>Status</th>
                        <th>Viewed</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($applications as $application)
                        <tr>
                            <td>{{ $application->application_number }}</td>
                            <td>
                                <strong>{{ $application->full_name }}</strong>
                                <p class="tich-caption">{{ $application->email }} · {{ $application->phone_number }}</p>
                            </td>
                            <td>{{ $application->vacancy->job_title ?? 'N/A' }}</td>
                            <td class="tich-caption">{{ $application->created_at->format('Y-m-d') }}</td>
                            <td>
                                @if ($application->status == 'submitted')
                                    <span class="tich-badge tich-badge--info">Submitted</span>
                                @elseif ($application->status == 'under_review')
                                    <span class="tich-badge tich-badge--warning">Under Review</span>
                                @elseif ($application->status == 'shortlisted')
                                    <span class="tich-badge tich-badge--success">Shortlisted</span>
                                @elseif ($application->status == 'rejected')
                                    <span class="tich-badge tich-badge--danger">Rejected</span>
                                @elseif ($application->status == 'offered')
                                    <span class="tich-badge tich-badge--success">Offered</span>
                                @else
                                    <span class="tich-badge">{{ ucfirst($application->status) }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($application->is_viewed)
                                    <span class="tich-badge tich-badge--success">Yes</span>
                                @else
                                    <span class="tich-badge tich-badge--warning">No</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('hr.recruitment.show', $application) }}" class="tich-btn tich-btn-ghost">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="tich-table-empty">No applications found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($applications->hasPages())
            <div class="tich-mt-6">
                {{ $applications->links() }}
            </div>
        @endif
    </div>
@endsection

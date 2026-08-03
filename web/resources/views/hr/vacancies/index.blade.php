@extends('layouts.hr')

@section('title', 'Vacancies')

@section('hr-content')
    <div class="tich-mb-8">
        <div class="tich-flex tich-flex--between tich-flex--start">
            <div>
                <h1 class="tich-h1">Vacancies</h1>
                <p class="tich-text tich-mt-2">Manage job openings. Published vacancies appear on the public careers page.</p>
            </div>
            <a href="{{ route('hr.vacancies.create') }}" class="tich-btn tich-btn-primary">+ New Vacancy</a>
        </div>
    </div>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Vacancy No.</th>
                        <th>Job Title</th>
                        <th>Department</th>
                        <th>Type</th>
                        <th>Slots</th>
                        <th>Closing Date</th>
                        <th>Published</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vacancies as $vacancy)
                        <tr>
                            <td>{{ $vacancy->vacancy_number }}</td>
                            <td>
                                <strong>{{ $vacancy->job_title }}</strong>
                                <p class="tich-caption">{{ Str::limit($vacancy->job_description, 80) }}</p>
                            </td>
                            <td class="tich-caption">{{ $vacancy->department->dept_name ?? '—' }}</td>
                            <td class="tich-caption">{{ ucfirst($vacancy->employment_type) }}</td>
                            <td class="tich-caption">{{ $vacancy->slots_available }}</td>
                            <td class="tich-caption">{{ $vacancy->closing_date?->format('Y-m-d') ?? 'Open' }}</td>
                            <td>
                                @if ($vacancy->is_published)
                                    <span class="tich-badge tich-badge--success">Published</span>
                                @else
                                    <span class="tich-badge tich-badge--warning">Draft</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('hr.vacancies.show', $vacancy) }}" class="tich-btn tich-btn-ghost">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="tich-table-empty">No vacancies found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($vacancies->hasPages())
            <div class="tich-mt-6">
                {{ $vacancies->links() }}
            </div>
        @endif
    </div>
@endsection

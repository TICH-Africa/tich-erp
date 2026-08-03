@extends('layouts.hr')

@section('title', $vacancy->job_title . ' - Vacancy')

@section('hr-content')
    <div class="tich-mb-8">
        <a href="{{ route('hr.vacancies.index') }}" class="tich-btn tich-btn-ghost">&larr; Back to vacancies</a>
    </div>

    <article class="tich-card">
        <div class="tich-mb-6">
            <h1 class="tich-h1">{{ $vacancy->job_title }}</h1>
            <p class="tich-text tich-text--secondary tich-mt-2">
                {{ $vacancy->department->dept_name ?? 'General' }}
                &middot;
                {{ ucfirst($vacancy->employment_type) }}
                &middot;
                {{ $vacancy->slots_available }} position{{ $vacancy->slots_available > 1 ? 's' : '' }}
            </p>
        </div>

        <div class="tich-grid tich-grid--2 tich-mb-6">
            <div>
                <h2 class="tich-h3">Description</h2>
                <p class="tich-text tich-mt-2">{{ $vacancy->job_description }}</p>
            </div>
            <div>
                <h2 class="tich-h3">Requirements</h2>
                <p class="tich-text tich-mt-2">{{ $vacancy->requirements }}</p>
            </div>
        </div>

        <div class="tich-grid tich-grid--2 tich-mb-6">
            <div>
                <h2 class="tich-h3">Responsibilities</h2>
                <p class="tich-text tich-mt-2">{{ $vacancy->responsibilities }}</p>
            </div>
            <div>
                <h2 class="tich-h3">Details</h2>
                <ul class="tich-list">
                    <li><strong>Minimum Qualification:</strong> {{ $vacancy->min_qualification }}</li>
                    <li><strong>Closing Date:</strong> {{ $vacancy->closing_date?->format('M j, Y') ?? 'Open until filled' }}</li>
                    @if ($vacancy->salary_scale)
                        <li><strong>Salary Scale:</strong> {{ $vacancy->salary_scale }}</li>
                    @endif
                    @if ($vacancy->benefits)
                        <li><strong>Benefits:</strong> {{ $vacancy->benefits }}</li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="tich-mt-6">
            @if ($vacancy->is_published)
                <a href="{{ route('careers.show', $vacancy) }}" class="tich-btn tich-btn-primary" target="_blank">View Public Page</a>
            @else
                <form method="POST" action="{{ route('hr.vacancies.toggle-publish', $vacancy) }}" class="tich-d-inline">
                    @csrf
                    @method('POST')
                    <button type="submit" class="tich-btn tich-btn-primary">Publish</button>
                </form>
            @endif
            <a href="{{ route('hr.recruitment.index', ['vacancy_id' => $vacancy->id]) }}" class="tich-btn tich-btn-secondary">View Applications</a>
        </div>
    </article>
@endsection

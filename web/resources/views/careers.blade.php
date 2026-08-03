@extends('layouts.app')

@section('title', 'Careers')

@section('content')
    <section class="tich-section" id="careers">
        <div class="tich-container">
            <div class="tich-mb-8">
                <h1 class="tich-h1">Join Our Team</h1>
                <p class="tich-text tich-text--secondary">
                    Explore career opportunities at {{ $siteMeta['institution_name'] ?? 'TICH in Africa' }}.
                </p>
                <a href="{{ route('vacancies.track') }}" class="tich-btn tich-btn-ghost tich-mt-4">Track Application Status</a>
            </div>

            <form method="GET" action="{{ route('careers.index') }}" class="tich-card tich-mb-8">
                <div class="tich-grid tich-grid--3">
                    <div>
                        <label for="search" class="tich-label">Search</label>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="{{ $filters['search'] ?? '' }}"
                            placeholder="Job title, keyword..."
                            class="tich-input"
                        >
                    </div>
                    <div>
                        <label for="department_id" class="tich-label">Department</label>
                        <select id="department_id" name="department_id" class="tich-input">
                            <option value="">All departments</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}" {{ ($filters['department_id'] ?? '') == $department->id ? 'selected' : '' }}>
                                    {{ $department->dept_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="employment_type" class="tich-label">Employment Type</label>
                        <select id="employment_type" name="employment_type" class="tich-input">
                            <option value="">All types</option>
                            @foreach ($employmentTypes as $type)
                                <option value="{{ $type['value'] }}" {{ ($filters['employment_type'] ?? '') == $type['value'] ? 'selected' : '' }}>
                                    {{ $type['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="tich-mt-4">
                    <button type="submit" class="tich-btn tich-btn-primary">Filter</button>
                    @if ($filters['search'] || $filters['department_id'] || $filters['employment_type'])
                        <a href="{{ route('careers.index') }}" class="tich-btn tich-btn-ghost">Clear</a>
                    @endif
                </div>
            </form>

            @if ($vacancies->isEmpty())
                <div class="tich-card tich-text-center">
                    <p class="tich-text tich-text--secondary">No open positions match your criteria. Please check back later.</p>
                </div>
            @else
                <div class="tich-grid tich-grid--1">
                    @foreach ($vacancies as $vacancy)
                        <article class="tich-card tich-card--hover">
                            <div class="tich-flex tich-flex--between tich-flex--start">
                                <div>
                                    <h2 class="tich-h3">
                                        <a href="{{ route('careers.show', $vacancy) }}" class="tich-link">
                                            {{ $vacancy->job_title }}
                                        </a>
                                    </h2>
                                    <p class="tich-text tich-text--secondary tich-mt-2">
                                        {{ $vacancy->department->dept_name ?? 'General' }}
                                        &middot;
                                        {{ ucfirst($vacancy->employment_type) }}
                                        &middot;
                                        {{ $vacancy->slots_available }} position{{ $vacancy->slots_available > 1 ? 's' : '' }}
                                    </p>
                                    <p class="tich-text tich-mt-2">
                                        Closes {{ $vacancy->closing_date?->format('M j, Y') ?? 'Open until filled' }}
                                    </p>
                                </div>
                                <a href="{{ route('careers.show', $vacancy) }}" class="tich-btn tich-btn-secondary">View details</a>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="tich-mt-8">
                    {{ $vacancies->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection

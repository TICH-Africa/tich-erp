@extends('layouts.app')

@section('title', 'Careers')

@section('content')
    <section class="tich-section tich-careers-page" id="careers">
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
                <div class="tich-grid tich-grid--2 tich-careers-grid">
                    @foreach ($vacancies as $vacancy)
                        <article class="tich-card tich-card--hover tich-careers-card">
                            <h2 class="tich-h3">
                                <a href="{{ route('careers.show', $vacancy) }}" class="tich-link">
                                    {{ $vacancy->job_title }}
                                </a>
                            </h2>

                            <div class="tich-careers-card__meta">
                                <span class="tich-careers-tag">{{ $vacancy->department->dept_name ?? 'General' }}</span>
                                <span class="tich-careers-tag">{{ ucfirst($vacancy->employment_type) }}</span>
                                @if ($vacancy->position_grade)
                                    <span class="tich-careers-tag">{{ $vacancy->position_grade }}</span>
                                @endif
                                <span class="tich-careers-tag">{{ $vacancy->slots_available }} opening{{ $vacancy->slots_available > 1 ? 's' : '' }}</span>
                            </div>

                            @if ($vacancy->job_description)
                                <p class="tich-careers-card__excerpt">{{ Str::limit(strip_tags($vacancy->job_description), 160) }}</p>
                            @endif

                            <dl class="tich-careers-card__details">
                                @if ($vacancy->min_qualification)
                                    <div>
                                        <dt>Minimum qualification</dt>
                                        <dd>{{ $vacancy->min_qualification }}</dd>
                                    </div>
                                @endif
                                @if ($vacancy->salary_scale)
                                    <div>
                                        <dt>Salary scale</dt>
                                        <dd>{{ $vacancy->salary_scale }}</dd>
                                    </div>
                                @endif
                                @if ($vacancy->benefits)
                                    <div>
                                        <dt>Benefits</dt>
                                        <dd>{{ Str::limit($vacancy->benefits, 80) }}</dd>
                                    </div>
                                @endif
                                <div>
                                    <dt>Closing date</dt>
                                    <dd>{{ $vacancy->closing_date?->format('M j, Y') ?? 'Open until filled' }}</dd>
                                </div>
                            </dl>

                            <div class="tich-careers-card__actions">
                                <a href="{{ route('careers.show', $vacancy) }}" class="tich-btn tich-btn-secondary">View details</a>
                                <a href="{{ route('vacancies.apply.create', $vacancy) }}" class="tich-btn tich-btn-primary">Apply now</a>
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

@extends('layouts.app')

@section('title', 'Careers')
@section('meta_description', config('tich-seo.pages.careers.description'))

@section('content')
    <header class="tich-course-page-header">
        <div class="tich-container">
            <h1 class="tich-course-page-header__title">Join Our Team</h1>
            <p class="tich-course-page-header__lead">
                Explore career opportunities at {{ $siteMeta['institution_name'] ?? 'TICH in Africa' }}.
            </p>
            <p class="tich-mt-4">
                <a href="{{ route('vacancies.track') }}" class="tich-btn tich-btn-ghost" style="color: #e8f3dc; border-color: #6cab33;">Track Application Status</a>
            </p>
        </div>
    </header>

    <section class="tich-section tich-section--programs tich-careers-page" id="careers" data-live-search>
        <div class="tich-container">
            <div class="tich-course-filters tich-mb-6">
                <div class="tich-form-group" style="margin: 0;">
                    <label for="career-search" class="tich-label">Search</label>
                    <input
                        type="search"
                        id="career-search"
                        data-live-search-input
                        placeholder="Job title, keyword..."
                        class="tich-input"
                        style="width: min(100%, 16rem);"
                        autocomplete="off"
                        value="{{ $filters['search'] ?? '' }}"
                    >
                </div>
                <div class="tich-form-group" style="margin: 0;">
                    <label for="career-department" class="tich-label">Department</label>
                    <select id="career-department" class="tich-select" data-live-search-filter="department">
                        <option value="">All departments</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" {{ ($filters['department_id'] ?? '') == $department->id ? 'selected' : '' }}>
                                {{ $department->dept_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group" style="margin: 0;">
                    <label for="career-employment" class="tich-label">Employment Type</label>
                    <select id="career-employment" class="tich-select" data-live-search-filter="employment">
                        <option value="">All types</option>
                        @foreach ($employmentTypes as $type)
                            <option value="{{ $type['value'] }}" {{ ($filters['employment_type'] ?? '') == $type['value'] ? 'selected' : '' }}>
                                {{ $type['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if ($vacancies->isEmpty())
                <div class="tich-card tich-text-center">
                    <p class="tich-text tich-text--secondary">No open positions match your criteria. Please check back later.</p>
                </div>
            @else
                <div class="tich-course-grid tich-careers-grid">
                    @foreach ($vacancies as $vacancy)
                        @include('careers.partials.job-card', ['vacancy' => $vacancy])
                    @endforeach
                </div>
                <p class="tich-text tich-mt-6" data-live-search-empty hidden>No positions match your search.</p>
            @endif
        </div>
    </section>
@endsection

@extends('layouts.app')

@section('title', 'Programs & Courses')
@section('meta_description', config('tich-seo.pages.programs.description'))

@section('content')
    <header class="tich-course-page-header">
        <div class="tich-container">
            <h1 class="tich-course-page-header__title">Programs &amp; courses</h1>
            <p class="tich-course-page-header__lead">
                Explore TICH certificate and diploma programmes in community health practice, development, and health technology. Select a programme and start your online application.
            </p>
            @if ($usingFallback)
                <p class="tich-course-page-header__note">Programme catalogue will appear once academic records are published.</p>
            @endif
        </div>
    </header>

    <div data-live-search>
        <section class="tich-section tich-section--programs-toolbar">
            <div class="tich-container">
                <div class="tich-course-filters">
                    <div class="tich-form-group" style="margin: 0;">
                        <label for="program-search" class="tich-label">Search programs</label>
                        <input
                            type="search"
                            id="program-search"
                            data-live-search-input
                            placeholder="Program name or code..."
                            class="tich-input"
                            style="width: min(100%, 16rem);"
                            autocomplete="off"
                        >
                    </div>
                    <div class="tich-form-group" style="margin: 0;">
                        <label for="program-department" class="tich-label">Department</label>
                        <select id="program-department" class="tich-select" data-live-search-filter="department">
                            <option value="">All departments</option>
                            @foreach ($departments as $dept)
                                <option value="{{ strtolower($dept->dept_code) }}">{{ $dept->dept_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </section>

        @if ($featured)
            <section class="tich-section tich-section--programs" style="padding-top: 0;">
                <div class="tich-container">
                    <p class="tich-course-featured-label">Featured programme</p>
                    @include('programs.partials.course-card', [
                        'program' => $featured,
                        'extraClass' => 'tich-course-card--featured',
                        'headingTag' => 'h2',
                        'excerptLimit' => 220,
                        'entryLimit' => 100,
                    ])
                </div>
            </section>
        @endif

        <section class="tich-section tich-section--programs" id="catalog">
            <div class="tich-container">
                <div class="tich-section__intro">
                    <h2 class="tich-h2">All programmes</h2>
                    <p class="tich-text">Choose a programme to view requirements and begin your application.</p>
                </div>

                <div class="tich-course-grid">
                    @forelse ($programs as $program)
                        @include('programs.partials.course-card', [
                            'program' => $program,
                            'excerptLimit' => 140,
                            'entryLimit' => 90,
                        ])
                    @empty
                        <p class="tich-text">Programme catalogue will be published soon.</p>
                    @endforelse
                </div>
                <p class="tich-text tich-mt-6" data-live-search-empty hidden>No programmes match your search.</p>
            </div>
        </section>
    </div>
@endsection

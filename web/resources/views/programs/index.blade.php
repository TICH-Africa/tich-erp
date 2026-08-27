@extends('layouts.app')

@section('title', 'Programs & Courses')
@section('meta_description', config('tich-seo.pages.programs.description'))

@section('content')
    <section class="tich-section tich-section--hero-plain" aria-labelledby="programs-heading">
        <div class="tich-container">
            <h1 id="programs-heading" class="tich-h1">Programs &amp; courses</h1>
            <p class="tich-text tich-mt-4" style="max-width: 42rem;">
                Explore TICH certificate and diploma programmes in community health practice, development, and health technology. Select a programme and start your online application.
            </p>
            @if ($usingFallback)
                <p class="tich-caption tich-mt-2">Showing default programme catalogue until academic records are published in the CMS.</p>
            @endif
        </div>
    </section>

    <section class="tich-section" style="padding-top: 0;">
        <div class="tich-container">
            <form method="GET" action="{{ route('programs.index') }}" class="tich-flex-wrap" style="gap: 1rem; align-items: end;">
                <div class="tich-form-group" style="margin: 0;">
                    <label for="search" class="tich-label">Search programs</label>
                    <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Program name or code..." class="tich-input" style="width: 200px;">
                </div>
                <div class="tich-form-group" style="margin: 0;">
                    <label for="department" class="tich-label">Department</label>
                    <select id="department" name="department" class="tich-select" onchange="this.form.submit()">
                        <option value="">All departments</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->dept_code }}" {{ request('department') === $dept->dept_code ? 'selected' : '' }}>
                                {{ $dept->dept_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="tich-btn tich-btn-primary" style="height: fit-content;">Search</button>
                @if (request('search') || request('department'))
                    <a href="{{ route('programs.index') }}" class="tich-btn tich-btn-secondary" style="height: fit-content;">Clear</a>
                @endif
            </form>
        </div>
    </section>

    @if ($featured)
        <section class="tich-section" style="padding-top: 0;">
            <div class="tich-container">
                <article class="tich-card tich-featured-program">
                    <div class="tich-featured-program__media">
                        @include('programs.partials.cover-image', ['program' => $featured])
                    </div>
                    <div class="tich-featured-program__body">
                        <p class="tich-caption">Featured programme</p>
                        <div class="tich-grid tich-grid--2 tich-mt-4" style="gap: 2rem; align-items: start;">
                            <div>
                                <h2 class="tich-h2">{{ $featured->program_name }}</h2>
                                <p class="tich-caption tich-mt-2">{{ strtoupper($featured->program_code) }} · {{ strtoupper(str_replace('_', ' ', $featured->program_type)) }}</p>
                                @if (!empty($featured->homepage_tagline))
                                    <p class="tich-text tich-mt-4">{{ $featured->homepage_tagline }}</p>
                                @endif
                                <ul class="tich-program-card__meta tich-mt-4">
                                    @if (!empty($featured->duration_months))
                                        <li><span class="tich-caption">Duration</span> {{ $featured->duration_months }} months</li>
                                    @endif
                                    @if (!empty($featured->regulatory_body))
                                        <li><span class="tich-caption">Accreditation</span> {{ $featured->regulatory_body }}</li>
                                    @endif
                                    @if (!empty($featured->entry_requirements))
                                        <li><span class="tich-caption">Entry</span> {{ $featured->entry_requirements }}</li>
                                    @endif
                                    @if (!empty($featured->fee_display))
                                        <li><span class="tich-caption">Fees</span> {{ $featured->fee_display }}</li>
                                    @endif
                                </ul>
                                <div class="tich-flex-wrap tich-mt-6">
                                    <a href="{{ $featured->url ?? route('programs.show', $featured->program_code) }}" class="tich-btn tich-btn-primary">View programme</a>
                                    <a href="{{ $featured->apply_url ?? route('apply.index', ['program' => $featured->program_code]) }}" class="tich-btn tich-btn-blue">Apply now</a>
                                </div>
                            </div>
                            <div class="tich-featured-program__aside">
                                <h3 class="tich-h3">Why this programme?</h3>
                                <p class="tich-text tich-mt-2">Designed for frontline community health practice across Western Kenya - combining classroom learning, field placement, and NITA/CDACC-aligned assessment.</p>
                                <p class="tich-caption tich-mt-4">Applications are reviewed by the academic department. You will receive your application number immediately after submission.</p>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </section>
    @endif

    <section class="tich-section" id="catalog">
        <div class="tich-container">
            <div class="tich-section__intro">
                <h2 class="tich-h2">All programmes</h2>
                <p class="tich-text">Choose a programme to view requirements and begin your application.</p>
            </div>

            <div class="tich-grid tich-grid--3">
                @forelse ($programs as $program)
                    <article class="tich-card tich-program-card">
                        <div class="tich-program-card__media">
                            @include('programs.partials.cover-image', ['program' => $program])
                        </div>
                        <div class="tich-program-card__body">
                            <p class="tich-caption">{{ strtoupper($program->program_code) }} · {{ strtoupper(str_replace('_', ' ', $program->program_type)) }}</p>
                            <h3 class="tich-h3 tich-mt-2">{{ $program->program_name }}</h3>
                            @if (!empty($program->homepage_tagline))
                                <p class="tich-text tich-mt-2">{{ $program->homepage_tagline }}</p>
                            @endif

                            <ul class="tich-program-card__meta tich-mt-4">
                                @if (!empty($program->duration_months))
                                    <li><span class="tich-caption">Duration</span> {{ $program->duration_months }} months</li>
                                @endif
                                @if (!empty($program->regulatory_body))
                                    <li><span class="tich-caption">Accreditation</span> {{ $program->regulatory_body }}</li>
                                @endif
                                @if (!empty($program->entry_requirements))
                                    <li><span class="tich-caption">Entry</span> {{ \Illuminate\Support\Str::limit($program->entry_requirements, 90) }}</li>
                                @endif
                                @if (!empty($program->fee_display))
                                    <li><span class="tich-caption">Fees</span> {{ $program->fee_display }}</li>
                                @endif
                            </ul>

                            <div class="tich-flex-wrap tich-mt-4" style="gap: 0.5rem;">
                                <a href="{{ $program->url ?? route('programs.show', $program->program_code) }}" class="tich-btn tich-btn-primary">View programme</a>
                                <a href="{{ $program->apply_url ?? route('apply.index', ['program' => $program->program_code]) }}" class="tich-btn tich-btn-secondary">Apply now</a>
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="tich-text">Programme catalogue will be published soon.</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection

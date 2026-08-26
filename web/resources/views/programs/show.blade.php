@extends('layouts.app')

@section('title', $program->program_name)

@section('content')
    <article class="tich-program-show">
        <header class="tich-program-show__hero">
            @if (!empty($program->cover_image_url))
                <div class="tich-program-show__hero-media" aria-hidden="true">
                    <img
                        src="{{ $program->cover_image_url }}"
                        alt=""
                        class="tich-program-show__hero-image"
                    >
                    <div class="tich-program-show__hero-overlay"></div>
                </div>
            @else
                <div class="tich-program-show__hero-fallback" aria-hidden="true"></div>
            @endif

            <div class="tich-container tich-program-show__hero-content">
                <p class="tich-program-show__back">
                    <a href="{{ route('programs.index') }}" class="tich-program-show__back-link">← All programmes</a>
                </p>
                <p class="tich-program-show__eyebrow">{{ strtoupper($program->program_code) }} · {{ strtoupper(str_replace('_', ' ', $program->program_type ?? 'PROGRAMME')) }}</p>
                <h1 class="tich-program-show__title">{{ $program->program_name }}</h1>
                @if (!empty($program->homepage_tagline))
                    <p class="tich-program-show__lead">{{ $program->homepage_tagline }}</p>
                @endif
            </div>
        </header>

        <div class="tich-container tich-program-show__body">
            <div class="tich-program-show__layout">
                <div class="tich-program-show__main">
                    <h2 class="tich-h3">About this programme</h2>
                    @if (!empty($program->homepage_tagline))
                        <p class="tich-text tich-mt-4">{{ $program->homepage_tagline }}</p>
                    @endif

                    @if (!empty($program->entry_requirements))
                        <h3 class="tich-h3 tich-mt-8">Entry requirements</h3>
                        <div class="tich-program-show__copy tich-mt-4">{{ $program->entry_requirements }}</div>
                    @endif

                    <p class="tich-caption tich-mt-8">
                        Applications are reviewed by the academic department. You will receive your application number immediately after submission.
                    </p>
                </div>

                <aside class="tich-program-show__aside">
                    <h2 class="tich-h3">Details</h2>
                    <dl class="tich-program-show__meta">
                        <div>
                            <dt>Code</dt>
                            <dd>{{ strtoupper($program->program_code) }}</dd>
                        </div>
                        <div>
                            <dt>Type</dt>
                            <dd>{{ ucfirst(str_replace('_', ' ', $program->program_type ?? 'programme')) }}</dd>
                        </div>
                        @if (!empty($program->department_name))
                            <div>
                                <dt>Department</dt>
                                <dd>{{ $program->department_name }}</dd>
                            </div>
                        @endif
                        @if (!empty($program->duration_months))
                            <div>
                                <dt>Duration</dt>
                                <dd>{{ $program->duration_months }} months</dd>
                            </div>
                        @endif
                        @if (!empty($program->regulatory_body))
                            <div>
                                <dt>Accreditation</dt>
                                <dd>{{ $program->regulatory_body }}</dd>
                            </div>
                        @endif
                        @if (!empty($program->fee_display))
                            <div>
                                <dt>Fees</dt>
                                <dd>{{ $program->fee_display }}</dd>
                            </div>
                        @endif
                    </dl>

                    <div class="tich-program-show__actions">
                        <a href="{{ $program->apply_url ?? route('apply.index', ['program' => $program->program_code]) }}" class="tich-btn tich-btn-primary">Apply now</a>
                        <a href="{{ route('programs.index') }}" class="tich-btn tich-btn-secondary">Browse programmes</a>
                    </div>
                </aside>
            </div>
        </div>
    </article>
@endsection

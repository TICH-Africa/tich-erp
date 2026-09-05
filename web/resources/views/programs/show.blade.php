@extends('layouts.app')

@section('title', $program->program_name)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($program->homepage_tagline ?: $program->entry_requirements ?: $program->program_name), 160, ''))

@php
    $seo = [
        'type' => 'website',
        'image' => $program->cover_image_url ?? null,
        'url' => route('programs.show', $program->program_code),
    ];
@endphp

@section('content')
    <article class="tich-program-show" itemscope itemtype="https://schema.org/Course">
        <header class="tich-program-show__hero">
            <div class="tich-container tich-program-show__hero-content">
                <p class="tich-program-show__back">
                    <a href="{{ route('programs.index') }}" class="tich-program-show__back-link"><i class="tich-icon-arrow-left"></i> All programmes</a>
                </p>
                <p class="tich-program-show__eyebrow">{{ strtoupper($program->program_code) }} · {{ strtoupper(str_replace('_', ' ', $program->program_type ?? 'PROGRAMME')) }}</p>
                <h1 class="tich-program-show__title" itemprop="name">{{ $program->program_name }}</h1>
                @if (!empty($program->homepage_tagline))
                    <p class="tich-program-show__lead" itemprop="description">{{ \Illuminate\Support\Str::limit(strip_tags($program->homepage_tagline), 180) }}</p>
                @endif
            </div>
        </header>

        <div class="tich-container tich-program-show__body">
            <div class="tich-program-show__layout">
                <div class="tich-program-show__main">
                    @if (!empty($program->cover_image_url))
                        <figure class="tich-program-show__figure">
                            <img
                                src="{{ $program->cover_image_url }}"
                                alt="{{ $program->program_name }}"
                                class="tich-program-show__figure-image"
                            >
                        </figure>
                    @endif

                    <h2 class="tich-h3">About this programme</h2>
                    @if (!empty($program->homepage_tagline))
                        <div class="tich-text tich-mt-4 tich-prose">{!! $program->homepage_tagline !!}</div>
                    @endif

                    @if (!empty($program->entry_requirements))
                        <h3 class="tich-h3 tich-mt-8">Entry requirements</h3>
                        <div class="tich-program-show__copy tich-mt-4 tich-prose">{!! $program->entry_requirements !!}</div>
                    @endif

                    <p class="tich-caption tich-mt-8">
                        Applications are reviewed by the academic department. You will receive your application number immediately after submission.
                    </p>
                </div>

                <aside class="tich-program-show__aside">
                    <h2 class="tich-h3">Details</h2>
                    <dl class="tich-program-show__meta">
                       
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

@section('seo_jsonld')
    @include('partials.seo-jsonld-organization')
    @php
        $courseLd = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Course',
            'name' => $program->program_name,
            'description' => $program->homepage_tagline ?: $program->entry_requirements,
            'provider' => [
                '@type' => 'EducationalOrganization',
                'name' => $siteMeta['institution_name'] ?? 'TICH in Africa',
                'url' => url('/'),
            ],
            'url' => route('programs.show', $program->program_code),
            'image' => $program->cover_image_url ?? null,
            'timeRequired' => ! empty($program->duration_months) ? 'P'.(int) $program->duration_months.'M' : null,
            'educationalCredentialAwarded' => $program->program_type ?? null,
        ], fn ($v) => $v !== null && $v !== '');
    @endphp
    <script type="application/ld+json">{!! json_encode($courseLd, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endsection

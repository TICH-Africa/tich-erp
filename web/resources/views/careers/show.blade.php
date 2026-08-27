@extends('layouts.app')

@section('title', $vacancy->job_title . ' - Careers')
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($vacancy->job_description ?: $vacancy->job_title), 160, ''))

@php
    $seo = [
        'type' => 'article',
        'url' => route('careers.show', $vacancy),
    ];
@endphp

@section('content')
    <section class="tich-section tich-careers-page" id="careers" aria-labelledby="vacancy-heading">
        <div class="tich-container">
            <a href="{{ route('careers.index') }}" class="tich-btn tich-btn-ghost tich-mb-6">&larr; Back to careers</a>

            <article class="tich-card" itemscope itemtype="https://schema.org/JobPosting">
                <div class="tich-mb-6">
                    <h1 id="vacancy-heading" class="tich-h1" itemprop="title">{{ $vacancy->job_title }}</h1>
                    <p class="tich-text tich-text--secondary tich-mt-2">
                        <span itemprop="hiringOrganization" itemscope itemtype="https://schema.org/Organization">
                            <meta itemprop="name" content="{{ $siteMeta['institution_name'] ?? 'TICH in Africa' }}">
                        </span>
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
                    <a href="{{ route('vacancies.apply.create', $vacancy) }}" class="tich-btn tich-btn-primary">Apply Now</a>
                </div>
            </article>
        </div>
    </section>
@endsection

@section('seo_jsonld')
    @include('partials.seo-jsonld-organization')
    @php
        $jobLd = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'JobPosting',
            'title' => $vacancy->job_title,
            'description' => $vacancy->job_description,
            'datePosted' => optional($vacancy->published_on ?? $vacancy->created_at)->toAtomString(),
            'validThrough' => $vacancy->closing_date
                ? $vacancy->closing_date->copy()->endOfDay()->toAtomString()
                : null,
            'employmentType' => strtoupper((string) $vacancy->employment_type),
            'hiringOrganization' => [
                '@type' => 'EducationalOrganization',
                'name' => $siteMeta['institution_name'] ?? 'TICH in Africa',
                'sameAs' => url('/'),
            ],
            'jobLocation' => [
                '@type' => 'Place',
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => 'Kisumu',
                    'addressCountry' => 'KE',
                ],
            ],
            'url' => route('careers.show', $vacancy),
        ], fn ($v) => $v !== null && $v !== '');
    @endphp
    <script type="application/ld+json">{!! json_encode($jobLd, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endsection

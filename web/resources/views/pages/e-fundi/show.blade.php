@extends('layouts.app')

@section('title', $opportunity->title . ' — e-Fundi')
@section('meta_description', \Illuminate\Support\Str::limit($opportunity->summary ?? strip_tags($opportunity->description ?? ''), 160))

@section('content')
    <section class="tich-section tich-section--white">
        <div class="tich-container" style="max-width: 48rem;">
            <p class="tich-caption">
                <a href="{{ route('e-fundi.index') }}">e-Fundi</a>
                · {{ $opportunity->typeLabel() }}
                · {{ $opportunity->scopeLabel() }}
            </p>
            <h1 class="tich-h1 tich-mt-2">{{ $opportunity->title }}</h1>

            @php
                $orgName = $opportunity->organization_name
                    ?: ($opportunity->isInternal() ? ($siteMeta['institution_name'] ?? 'TICH in Africa') : null);
            @endphp
            @if ($orgName)
                <p class="tich-text tich-mt-2"><strong>{{ $orgName }}</strong>@if ($opportunity->location) — {{ $opportunity->location }}@endif</p>
            @endif

            @if (! $opportunity->isOpen())
                <p class="tich-badge tich-badge--neutral tich-mt-4">Applications closed</p>
            @elseif ($opportunity->isInternal() && $opportunity->jobVacancy?->closing_date)
                <p class="tich-caption tich-mt-4">Apply by {{ $opportunity->jobVacancy->closing_date->format('l, j F Y') }} (HR careers deadline)</p>
            @elseif ($opportunity->application_deadline)
                <p class="tich-caption tich-mt-4">Apply by {{ $opportunity->application_deadline->format('l, j F Y') }}</p>
            @endif

            @if ($opportunity->summary)
                <p class="tich-text tich-mt-6">{{ $opportunity->summary }}</p>
            @endif

            @if ($opportunity->description)
                <div class="tich-text tich-mt-6 tich-prose">{!! nl2br(e($opportunity->description)) !!}</div>
            @endif

            @if ($opportunity->isOpen() && $opportunity->isInternal() && $opportunity->jobVacancy)
                <p class="tich-text tich-mt-8">
                    Applications for this role are handled through HR, using the same process as the public careers page.
                    You will submit your CV and details online; HR will review and follow the normal recruitment steps.
                </p>
                <div class="tich-mt-4 tich-flex tich-flex-wrap tich-gap-3">
                    <a href="{{ route('vacancies.apply.create', $opportunity->jobVacancy) }}" class="tich-btn tich-btn-primary">Apply now</a>
                    <a href="{{ route('careers.show', $opportunity->jobVacancy) }}" class="tich-btn tich-btn-secondary">View full vacancy</a>
                    <a href="{{ route('vacancies.track') }}" class="tich-btn tich-btn-ghost">Track application</a>
                </div>
            @else
                <div class="tich-mt-8 tich-flex tich-flex-wrap tich-gap-3">
                    @if ($opportunity->application_url && $opportunity->isOpen())
                        <a href="{{ $opportunity->application_url }}" class="tich-btn tich-btn-primary" target="_blank" rel="noopener noreferrer">Apply online</a>
                    @endif
                    @if ($opportunity->application_email && $opportunity->isOpen())
                        <a href="mailto:{{ $opportunity->application_email }}" class="tich-btn tich-btn-secondary">Email to apply</a>
                    @endif
                </div>
            @endif
        </div>
    </section>
@endsection
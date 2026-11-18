@extends('layouts.app')

@section('title', $opportunity->title . ' — e-Fundi')
@section('meta_description', \Illuminate\Support\Str::limit($opportunity->summary ?? strip_tags($opportunity->description ?? ''), 160))

@section('content')
    <section class="tich-section tich-section--white">
        <div class="tich-container" style="max-width: 48rem;">
            <p class="tich-caption">
                <a href="{{ route('e-fundi.index') }}">e-Fundi</a>
                · {{ $opportunity->typeLabel() }}
            </p>
            <h1 class="tich-h1 tich-mt-2">{{ $opportunity->title }}</h1>

            @if ($opportunity->organization_name)
                <p class="tich-text tich-mt-2"><strong>{{ $opportunity->organization_name }}</strong>@if ($opportunity->location) — {{ $opportunity->location }}@endif</p>
            @endif

            @if (! $opportunity->isOpen())
                <p class="tich-badge tich-badge--neutral tich-mt-4">Applications closed</p>
            @elseif ($opportunity->application_deadline)
                <p class="tich-caption tich-mt-4">Apply by {{ $opportunity->application_deadline->format('l, j F Y') }}</p>
            @endif

            @if ($opportunity->summary)
                <p class="tich-text tich-mt-6">{{ $opportunity->summary }}</p>
            @endif

            @if ($opportunity->description)
                <div class="tich-text tich-mt-6 tich-prose">{!! nl2br(e($opportunity->description)) !!}</div>
            @endif

            <div class="tich-mt-8 tich-flex tich-flex-wrap tich-gap-3">
                @if ($opportunity->application_url && $opportunity->isOpen())
                    <a href="{{ $opportunity->application_url }}" class="tich-btn tich-btn-primary" target="_blank" rel="noopener noreferrer">Apply online</a>
                @endif
                @if ($opportunity->application_email && $opportunity->isOpen())
                    <a href="mailto:{{ $opportunity->application_email }}" class="tich-btn tich-btn-secondary">Email to apply</a>
                @endif
            </div>
        </div>
    </section>
@endsection
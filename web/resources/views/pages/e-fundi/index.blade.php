@extends('layouts.app')

@section('title', 'e-Fundi — Student opportunities')
@section('meta_description', 'Jobs, internships, scholarships, and career opportunities for TICH students.')

@section('content')
    <x-animated-section animation="top">
        <section class="tich-section tich-section--white" aria-labelledby="efundi-heading">
            <div class="tich-container">
                <header class="tich-mb-8">
                    <h1 id="efundi-heading" class="tich-h1">e-Fundi</h1>
                    <p class="tich-text tich-mt-2">
                        Jobs, internships, scholarships, and other opportunities for current students. New listings also appear in your student portal notifications.
                    </p>
                </header>

                <div class="tich-flex tich-flex-wrap tich-gap-2 tich-mb-6" role="tablist" aria-label="Filter by type">
                    <a href="{{ route('e-fundi.index') }}" class="tich-btn {{ empty($filterType) ? 'tich-btn-primary' : 'tich-btn-secondary' }} tich-btn-sm">All</a>
                    @foreach ($types as $value => $label)
                        <a href="{{ route('e-fundi.index', ['type' => $value]) }}" class="tich-btn {{ $filterType === $value ? 'tich-btn-primary' : 'tich-btn-secondary' }} tich-btn-sm">{{ $label }}</a>
                    @endforeach
                </div>

                <div class="tich-grid" style="gap: 1.25rem;">
                    @forelse ($opportunities as $item)
                        <article class="tich-card tich-card--pad">
                            <div class="tich-flex tich-flex-wrap tich-justify-between tich-gap-2">
                                <div>
                                    <span class="tich-badge tich-badge--pending">{{ $item->typeLabel() }}</span>
                                    <h2 class="tich-h2 tich-mt-2">
                                        <a href="{{ route('e-fundi.show', $item) }}">{{ $item->title }}</a>
                                    </h2>
                                    @if ($item->organization_name)
                                        <p class="tich-caption tich-mt-1">{{ $item->organization_name }}@if ($item->location) · {{ $item->location }}@endif</p>
                                    @endif
                                </div>
                                @if ($item->application_deadline)
                                    <p class="tich-caption">Apply by {{ $item->application_deadline->format('j M Y') }}</p>
                                @endif
                            </div>
                            @if ($item->summary)
                                <p class="tich-text tich-mt-3">{{ $item->summary }}</p>
                            @endif
                            <a href="{{ route('e-fundi.show', $item) }}" class="tich-link tich-mt-3" style="display:inline-block;">Read more</a>
                        </article>
                    @empty
                        <p class="tich-text">No open opportunities right now. Check back later.</p>
                    @endforelse
                </div>
            </div>
        </section>
    </x-animated-section>
@endsection
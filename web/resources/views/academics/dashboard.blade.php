@extends('layouts.academics')

@section('academics-content')
    <div class="tich-section__intro" style="text-align: left;">
        <h1 class="tich-h1" style="font-size: 2rem;">Curriculum hub</h1>
        <p class="tich-text">Course versioning, unit catalog, department mapping, and academic calendar configuration.</p>
    </div>

    <div class="tich-grid tich-grid--3 tich-mt-8" style="gap: 1.5rem;">
        <article class="tich-card tich-stat">
            <p class="tich-caption">Learning departments</p>
            <p class="tich-stat__value">{{ $stats['departments'] }}</p>
            @if ($stats['pending_departments'] > 0)
                <p class="tich-text tich-mt-2">{{ $stats['pending_departments'] }} pending CEO sign-off</p>
            @endif
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Catalog units</p>
            <p class="tich-stat__value">{{ $stats['units'] }}</p>
            @if ($stats['pending_units'] > 0)
                <p class="tich-text tich-mt-2">{{ $stats['pending_units'] }} pending registry</p>
            @endif
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Curriculum versions</p>
            <p class="tich-stat__value">{{ $stats['published_versions'] }}</p>
            <p class="tich-text tich-mt-2">{{ $stats['draft_versions'] }} in workflow</p>
        </article>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-8" style="gap: 1.5rem; align-items: start;">
        <article class="tich-card">
            <h2 class="tich-h3">Quick links</h2>
            <ul class="tich-mt-4" style="margin: 0; padding-left: 1.25rem;">
                <li class="tich-text"><a href="{{ route('academics.departments.index') }}" class="tich-link">Initialize departments &amp; profiles</a></li>
                <li class="tich-text tich-mt-2"><a href="{{ route('academics.units.index') }}" class="tich-link">Manage unit catalog</a></li>
                <li class="tich-text tich-mt-2"><a href="{{ route('academics.programs.index') }}" class="tich-link">Map units to programmes</a></li>
                @can('academics.calendar')
                    <li class="tich-text tich-mt-2"><a href="{{ route('academics.calendar.index') }}" class="tich-link">Configure academic calendar</a></li>
                @endcan
            </ul>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Downstream modules (Phase C hooks)</h2>
            <p class="tich-text tich-mt-4">Published curriculum versions feed these modules when they are built:</p>
            <ul class="tich-mt-4" style="margin: 0; padding-left: 1.25rem;">
                @foreach ($integrationHooks as $label => $table)
                    <li class="tich-text">{{ ucfirst($label) }} → <code>{{ $table }}</code></li>
                @endforeach
            </ul>
        </article>
    </div>
@endsection

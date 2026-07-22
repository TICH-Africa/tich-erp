<header class="tich-dept-header">
    <p class="tich-caption">{{ $overviewStats['category'] }} · {{ $department->dept_code }}</p>
    <h1 class="tich-h1 tich-dept-header__title">{{ $department->dept_name }}</h1>
    <p class="tich-text tich-dept-header__meta">
        Manage programmes, units, and applications for this school.
        @if ($academicsHub)
            Part of <a href="{{ route('departments.show', $academicsHub) }}" class="tich-link">{{ $academicsHub->dept_name }}</a>.
        @endif
    </p>
</header>

<div class="tich-grid tich-grid--4 tich-dept-stats">
    <article class="tich-card tich-stat">
        <p class="tich-caption">Programmes</p>
        <p class="tich-stat__value">{{ $overviewStats['program_count'] ?? 0 }}</p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Catalog units</p>
        <p class="tich-stat__value">{{ $overviewStats['unit_count'] ?? 0 }}</p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Pending applications</p>
        <p class="tich-stat__value">{{ $overviewStats['pending_applications'] ?? 0 }}</p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Curriculum profile</p>
        <p class="tich-stat__value" style="font-size: 1rem;">{{ ucfirst($overviewStats['curriculum_profile'] ?? 'standard') }}</p>
    </article>
</div>

@php
    $educationModules = collect($modules)->where('group', 'education');
    $admissionsModules = collect($modules)->where('group', 'admissions');
@endphp

@if ($educationModules->isNotEmpty())
    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">Education</h2>
            <p class="tich-text">Curriculum and unit management for {{ $department->dept_name }}.</p>
        </div>

        <div class="tich-grid tich-grid--2 tich-dept-cards">
            @foreach ($educationModules as $module)
                <article class="tich-card tich-dept-card tich-dept-card--accent">
                    <h3 class="tich-h3">{{ $module['label'] }}</h3>
                    <p class="tich-text tich-mt-2">{{ $module['description'] }}</p>
                    <a href="{{ route($module['route'], $module['params'] ?? []) }}" class="tich-btn tich-btn-primary tich-mt-4">Open</a>
                </article>
            @endforeach
        </div>
    </section>
@endif

@if ($admissionsModules->isNotEmpty())
    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">Admissions</h2>
            <p class="tich-text">Review student applications routed to this department.</p>
        </div>

        <div class="tich-grid tich-grid--2 tich-dept-cards">
            @foreach ($admissionsModules as $module)
                <article class="tich-card tich-dept-card">
                    <h3 class="tich-h3">{{ $module['label'] }}</h3>
                    <p class="tich-text tich-mt-2">{{ $module['description'] }}</p>
                    <a href="{{ route($module['route'], $module['params'] ?? []) }}" class="tich-btn tich-btn-secondary tich-mt-4">Open</a>
                </article>
            @endforeach
        </div>
    </section>
@endif

@if ($modules === [])
    <article class="tich-card tich-dept-empty tich-mt-8">
        <h2 class="tich-h3">No education tools available</h2>
        <p class="tich-text tich-mt-2">You do not have permission to manage curriculum or applications for this department.</p>
    </article>
@endif

@extends('layouts.academics')

@section('academics-content')
    @php($hub = ['department' => $department->id])

    <div class="tich-section__intro" style="text-align: left;">
        <p class="tich-caption">{{ $department->dept_code }} · Academics hub</p>
        <h1 class="tich-h1" style="font-size: 2rem;">{{ $department->dept_name }}</h1>
        <p class="tich-text">Course length, terms per year, unit catalog, semester/block mapping, and curriculum versioning for all learning departments under this hub.</p>
    </div>

    <div class="tich-grid tich-grid--4 tich-mt-8" style="gap: 1.5rem;">
        <article class="tich-card tich-stat">
            <p class="tich-caption">Learning departments</p>
            <p class="tich-stat__value">{{ $stats['departments'] }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Catalog units</p>
            <p class="tich-stat__value">{{ $stats['units'] }}</p>
            @if ($stats['pending_units'] > 0)
                <p class="tich-text tich-mt-2">{{ $stats['pending_units'] }} pending registry</p>
            @endif
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Published curriculum versions</p>
            <p class="tich-stat__value">{{ $stats['published_versions'] }}</p>
            <p class="tich-text tich-mt-2">{{ $stats['draft_versions'] }} in workflow</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Workload</p>
            <p class="tich-stat__value">{{ $workloadStats['total_weekly_hours'] ?? 0 }} hrs</p>
            <p class="tich-text tich-mt-2">{{ $workloadStats['over_capacity_count'] ?? 0 }} over capacity</p>
        </article>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-8" style="gap: 1.5rem; align-items: start;">
        <article class="tich-card">
            <h2 class="tich-h3">Quick links</h2>
            <ul class="tich-mt-4" style="margin: 0; padding-left: 1.25rem;">
                <li class="tich-text"><a href="{{ route('departments.academics.departments.index', $hub) }}" class="tich-link">Learning department profiles</a></li>
                <li class="tich-text tich-mt-2"><a href="{{ route('departments.academics.units.index', $hub) }}" class="tich-link">Manage unit catalog</a></li>
                <li class="tich-text tich-mt-2"><a href="{{ route('departments.academics.programs.index', $hub) }}" class="tich-link">Programme curriculum builder</a></li>
                <li class="tich-text tich-mt-2"><a href="{{ route('departments.academics.workload.index', $hub) }}" class="tich-link">Workload allocation</a></li>
                @can('academics.calendar')
                    <li class="tich-text tich-mt-2"><a href="{{ route('departments.academics.calendar.index', $hub) }}" class="tich-link">Academic calendar</a></li>
                @endcan
            </ul>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Learning departments</h2>
            <ul class="tich-mt-4" style="margin: 0; padding-left: 1.25rem;">
                @forelse ($learningDepartments as $learningDepartment)
                    <li class="tich-text">{{ $learningDepartment->dept_name }} ({{ $learningDepartment->dept_code }})</li>
                @empty
                    <li class="tich-text">No learning departments configured yet.</li>
                @endforelse
            </ul>
        </article>
    </div>
@endsection

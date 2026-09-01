@extends('layouts.academics')

@section('title', 'Academics Dashboard')

@section('academics-content')
    @php($hub = \App\Support\AcademicsRouteParams::for([
        'learning_department' => ($learningDepartment ?? null)?->id ?? request()->integer('learning_department') ?: null,
    ]))

    <x-page-toolbar
        :title="$department->dept_name"
        :meta="$department->dept_code . ' · Academics hub'"
    />

    <div class="tich-grid tich-grid--4 tich-mt-8" style="gap: 1.5rem;">
        <article class="tich-card tich-stat">
            <p class="tich-caption">Learning departments</p>
            <p class="tich-stat__value">{{ $stats['departments'] }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Programmes</p>
            <p class="tich-stat__value">{{ $stats['programs'] }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Catalog units</p>
            <p class="tich-stat__value">{{ $stats['units'] }}</p>
            @if ($stats['pending_units'] > 0)
                <p class="tich-text tich-mt-2">{{ $stats['pending_units'] }} pending registry</p>
            @endif
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Draft curriculum</p>
            <p class="tich-stat__value">{{ $stats['draft_versions'] }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Pending applications</p>
            <p class="tich-stat__value">{{ $stats['pending_applications'] }}</p>
            @if ($stats['pending_applications'] > 0)
                <a href="{{ route('departments.academics.applications.index', array_merge($hub, ['status' => 'pending'])) }}" class="tich-link tich-mt-2">Review now</a>
            @endif
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Published versions</p>
            <p class="tich-stat__value">{{ $stats['published_versions'] }}</p>
        </article>
        @if ($canApproveRegistry || $canApproveCeo)
            <article class="tich-card tich-stat">
                <p class="tich-caption">Registry approvals</p>
                <p class="tich-stat__value">{{ $stats['pending_units'] }}</p>
                <p class="tich-text tich-mt-2">Awaiting review</p>
            </article>
        @endif
    </div>

    <section class="tich-dashboard-charts tich-mt-8" aria-label="Academics analytics">
        <div class="tich-grid tich-grid--3" style="align-items: start;">
            <article class="tich-card tich-chart-card">
                <h3 class="tich-h3">Programmes by department</h3>
                <p class="tich-chart-card__meta">Distribution of academic programmes across learning departments</p>
                <div class="tich-chart-card__canvas-wrap">
                    <canvas id="academics-chart-programs-by-department" aria-label="Programmes by department chart" role="img"></canvas>
                </div>
            </article>

            <article class="tich-card tich-chart-card">
                <h3 class="tich-h3">Programme status</h3>
                <p class="tich-chart-card__meta">Current status distribution across programmes</p>
                <div class="tich-chart-card__canvas-wrap">
                    <canvas id="academics-chart-program-status" aria-label="Programme status chart" role="img"></canvas>
                </div>
            </article>

            <article class="tich-card tich-chart-card">
                <h3 class="tich-h3">Unit status</h3>
                <p class="tich-chart-card__meta">Unit catalog status breakdown</p>
                <div class="tich-chart-card__canvas-wrap">
                    <canvas id="academics-chart-unit-status" aria-label="Unit status chart" role="img"></canvas>
                </div>
            </article>
        </div>
    </section>

    <div class="tich-grid tich-grid--2 tich-mt-8" style="gap: 1.5rem; align-items: start;">
        <article class="tich-card tich-grid--2-span-full">
            <h2 class="tich-h3">Quick links</h2>
            <div class="tich-grid tich-grid--3 tich-mt-4" style="gap: 1rem;">
                <a href="{{ route('departments.academics.departments.index', $hub) }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;padding:1rem;">
                    <h3 class="tich-h4">Learning departments</h3>
                    <p class="tich-caption tich-mt-2">Manage department profiles and scope</p>
                </a>
                <a href="{{ route('departments.academics.units.index', $hub) }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;padding:1rem;">
                    <h3 class="tich-h4">Unit catalog</h3>
                    <p class="tich-caption tich-mt-2">Create, review, and publish units</p>
                </a>
                <a href="{{ route('departments.academics.programs.index', $hub) }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;padding:1rem;">
                    <h3 class="tich-h4">Programme curriculum</h3>
                    <p class="tich-caption tich-mt-2">Build and approve programme curricula</p>
                </a>
                @can('academics.calendar')
                    <a href="{{ route('departments.academics.calendar.index', $hub) }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;padding:1rem;">
                        <h3 class="tich-h4">Academic calendar</h3>
                        <p class="tich-caption tich-mt-2">Semester dates, exams, and holidays</p>
                    </a>
                @endcan
                @if ($canApproveRegistry)
                    <a href="{{ route('departments.academics.units.index', $hub) }}?status=pending_registry" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;padding:1rem;">
                        <h3 class="tich-h4">Registry review</h3>
                        <p class="tich-caption tich-mt-2">{{ $stats['pending_units'] }} pending units awaiting approval</p>
                    </a>
                @endif
                @if ($canApproveCeo)
                    <a href="{{ route('departments.academics.programs.index', $hub) }}?status=pending_ceo" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;padding:1rem;">
                        <h3 class="tich-h4">CEO approvals</h3>
                        <p class="tich-caption tich-mt-2">Curriculum versions pending executive sign-off</p>
                    </a>
                @endif
            </div>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Learning departments</h2>
            <ul class="tich-mt-4" style="margin: 0; padding-left: 1.25rem;">
                @forelse ($learningDepartments as $learningDepartmentItem)
                    <li class="tich-text">
                        <a href="{{ route('departments.academics.programs.index', array_merge($hub, ['learning_department' => $learningDepartmentItem->id])) }}" class="tich-link">{{ $learningDepartmentItem->dept_name }}</a>
                        ({{ $learningDepartmentItem->dept_code }})
                    </li>
                @empty
                    <li class="tich-text">No learning departments configured yet.</li>
                @endforelse
            </ul>
        </article>
    </div>

    @section('scripts')
        @parent
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
        <script id="academics-chart-data" type="application/json">@json($stats['chart'] ?? [])</script>
        <script src="{{ asset('js/tich-academics-dashboard.js') }}" defer></script>
    @endsection
@endsection

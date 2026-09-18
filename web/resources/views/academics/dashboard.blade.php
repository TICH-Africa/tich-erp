@extends('layouts.academics')

@section('title', 'Academics Dashboard')

@section('academics-content')
    @php($hub = \App\Support\AcademicsRouteParams::for([
        'learning_department' => ($learningDepartment ?? null)?->id ?? request()->integer('learning_department') ?: null,
    ]))

<div class="tich-mod-dash">
    <header class="tich-mod-dash__hero">
        <div class="tich-mod-dash__hero-copy">
            <p class="tich-mod-dash__eyebrow">{{ $department->dept_code }} · Academics hub</p>
            <h1 class="tich-mod-dash__title">{{ $department->dept_name }}</h1>
            <p class="tich-mod-dash__lede">Learning departments, programmes, unit catalog, and curriculum approvals — live overview.</p>
        </div>
        <div class="tich-mod-dash__hero-actions">
            <a href="{{ route('departments.academics.programs.index', $hub) }}" class="tich-btn tich-btn-primary">Programmes</a>
            <a href="{{ route('departments.academics.units.index', $hub) }}" class="tich-btn tich-btn-secondary">Unit catalog</a>
        </div>
    </header>

    <section class="tich-mod-dash__metrics" aria-label="Key academics metrics">
        <article class="tich-mod-dash__metric">
            <p class="tich-mod-dash__metric-label">Learning departments</p>
            <p class="tich-mod-dash__metric-value">{{ $stats['departments'] }}</p>
        </article>
        <article class="tich-mod-dash__metric tich-mod-dash__metric--info">
            <p class="tich-mod-dash__metric-label">Programmes</p>
            <p class="tich-mod-dash__metric-value">{{ $stats['programs'] }}</p>
        </article>
        <article class="tich-mod-dash__metric {{ ($stats['pending_units'] ?? 0) > 0 ? 'tich-mod-dash__metric--alert' : '' }}">
            <p class="tich-mod-dash__metric-label">Catalog units</p>
            <p class="tich-mod-dash__metric-value">{{ $stats['units'] }}</p>
            @if ($stats['pending_units'] > 0)
                <p class="tich-mod-dash__metric-hint">{{ $stats['pending_units'] }} pending registry</p>
            @endif
        </article>
        <article class="tich-mod-dash__metric">
            <p class="tich-mod-dash__metric-label">Draft curriculum</p>
            <p class="tich-mod-dash__metric-value">{{ $stats['draft_versions'] }}</p>
        </article>
        <article class="tich-mod-dash__metric {{ ($stats['pending_applications'] ?? 0) > 0 ? 'tich-mod-dash__metric--alert' : '' }}">
            <p class="tich-mod-dash__metric-label">Pending applications</p>
            <p class="tich-mod-dash__metric-value">{{ $stats['pending_applications'] }}</p>
            @if ($stats['pending_applications'] > 0)
                <a href="{{ route('departments.academics.applications.index', array_merge($hub, ['status' => 'pending'])) }}" class="tich-mod-dash__metric-link">Review</a>
            @endif
        </article>
        <article class="tich-mod-dash__metric tich-mod-dash__metric--ok">
            <p class="tich-mod-dash__metric-label">Published versions</p>
            <p class="tich-mod-dash__metric-value">{{ $stats['published_versions'] }}</p>
        </article>
        @if ($canApproveRegistry || $canApproveCeo)
            <article class="tich-mod-dash__metric {{ ($stats['pending_units'] ?? 0) > 0 ? 'tich-mod-dash__metric--alert' : '' }}">
                <p class="tich-mod-dash__metric-label">Registry approvals</p>
                <p class="tich-mod-dash__metric-value">{{ $stats['pending_units'] }}</p>
                <p class="tich-mod-dash__metric-hint">Awaiting review</p>
            </article>
        @endif
    </section>

    <section class="tich-mod-dash__charts" aria-label="Academics analytics">
        <article class="tich-mod-dash__chart">
            <div class="tich-mod-dash__chart-head">
                <h3 class="tich-mod-dash__chart-title">Programmes by department</h3>
                <p class="tich-mod-dash__chart-meta">Distribution across learning departments</p>
            </div>
            <div class="tich-chart-card__canvas-wrap">
                <canvas id="academics-chart-programs-by-department" aria-label="Programmes by department chart" role="img"></canvas>
            </div>
        </article>

        <article class="tich-mod-dash__chart">
            <div class="tich-mod-dash__chart-head">
                <h3 class="tich-mod-dash__chart-title">Programme status</h3>
                <p class="tich-mod-dash__chart-meta">Current status distribution</p>
            </div>
            <div class="tich-chart-card__canvas-wrap">
                <canvas id="academics-chart-program-status" aria-label="Programme status chart" role="img"></canvas>
            </div>
        </article>

        <article class="tich-mod-dash__chart">
            <div class="tich-mod-dash__chart-head">
                <h3 class="tich-mod-dash__chart-title">Unit status</h3>
                <p class="tich-mod-dash__chart-meta">Unit catalog status breakdown</p>
            </div>
            <div class="tich-chart-card__canvas-wrap">
                <canvas id="academics-chart-unit-status" aria-label="Unit status chart" role="img"></canvas>
            </div>
        </article>

        <article class="tich-mod-dash__panel" style="margin-top:0;">
            <div class="tich-mod-dash__panel-head">
                <div>
                    <p class="tich-mod-dash__panel-eyebrow">Directory</p>
                    <h2 class="tich-mod-dash__panel-title">Learning departments</h2>
                </div>
            </div>
            <ul style="margin:0;padding-left:1.25rem;">
                @forelse ($learningDepartments as $learningDepartmentItem)
                    <li class="tich-text" style="margin-top:0.4rem;">
                        <a href="{{ route('departments.academics.programs.index', array_merge($hub, ['learning_department' => $learningDepartmentItem->id])) }}" class="tich-link">{{ $learningDepartmentItem->dept_name }}</a>
                        <span class="tich-caption">({{ $learningDepartmentItem->dept_code }})</span>
                    </li>
                @empty
                    <li class="tich-text">No learning departments configured yet.</li>
                @endforelse
            </ul>
        </article>
    </section>

    <section class="tich-mod-dash__nav" aria-label="Academics shortcuts">
        <p class="tich-mod-dash__section-label">Quick routes</p>
        <div class="tich-mod-dash__nav-grid">
            <a href="{{ route('departments.academics.departments.index', $hub) }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">01</span>
                <h3 class="tich-mod-dash__nav-title">Learning departments</h3>
                <p class="tich-mod-dash__nav-text">Manage department profiles and scope.</p>
            </a>
            <a href="{{ route('departments.academics.units.index', $hub) }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">02</span>
                <h3 class="tich-mod-dash__nav-title">Unit catalog</h3>
                <p class="tich-mod-dash__nav-text">Create, review, and publish units.</p>
            </a>
            <a href="{{ route('departments.academics.programs.index', $hub) }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">03</span>
                <h3 class="tich-mod-dash__nav-title">Programme curriculum</h3>
                <p class="tich-mod-dash__nav-text">Build and approve programme curricula.</p>
            </a>
            @can('academics.calendar')
                <a href="{{ route('departments.academics.calendar.index', $hub) }}" class="tich-mod-dash__nav-card">
                    <span class="tich-mod-dash__nav-index">04</span>
                    <h3 class="tich-mod-dash__nav-title">Academic calendar</h3>
                    <p class="tich-mod-dash__nav-text">Semester dates, exams, and holidays.</p>
                </a>
            @endcan
            @if ($canApproveRegistry)
                <a href="{{ route('departments.academics.units.index', $hub) }}?status=pending_registry" class="tich-mod-dash__nav-card">
                    <span class="tich-mod-dash__nav-index">05</span>
                    <h3 class="tich-mod-dash__nav-title">Registry review</h3>
                    <p class="tich-mod-dash__nav-text">{{ $stats['pending_units'] }} pending units awaiting approval.</p>
                    @if ($stats['pending_units'] > 0)
                        <span class="tich-mod-dash__nav-badge">{{ $stats['pending_units'] }} pending</span>
                    @endif
                </a>
            @endif
            @if ($canApproveCeo)
                <a href="{{ route('departments.academics.programs.index', $hub) }}?status=pending_ceo" class="tich-mod-dash__nav-card">
                    <span class="tich-mod-dash__nav-index">06</span>
                    <h3 class="tich-mod-dash__nav-title">CEO approvals</h3>
                    <p class="tich-mod-dash__nav-text">Curriculum versions pending executive sign-off.</p>
                </a>
            @endif
        </div>
    </section>
</div>
@endsection

@section('scripts')
    @parent
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
    <script id="academics-chart-data" type="application/json">@json($stats['chart'] ?? [])</script>
    <script src="{{ asset('js/tich-academics-dashboard.js') }}" defer></script>
@endsection

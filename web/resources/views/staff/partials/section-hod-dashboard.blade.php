<article class="tich-card">
    <h2 class="tich-h3">HOD Dashboard - {{ $department->dept_name ?? 'Department' }}</h2>
    <p class="tich-text tich-mt-2">Manage programmes, units, and teaching allocations for your department.</p>
</article>

<div class="tich-grid tich-grid--4 tich-mt-6">
    <article class="tich-card tich-stat">
        <p class="tich-caption">Programmes</p>
        <p class="tich-stat__value">{{ $academicsStats['programs'] ?? 0 }}</p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Catalog units</p>
        <p class="tich-stat__value">{{ $academicsStats['units'] ?? 0 }}</p>
        @if (($academicsStats['pending_units'] ?? 0) > 0)
            <p class="tich-text tich-mt-2">{{ $academicsStats['pending_units'] }} pending registry</p>
        @endif
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Published curriculum</p>
        <p class="tich-stat__value">{{ $academicsStats['published_versions'] ?? 0 }}</p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Workload</p>
        <p class="tich-stat__value">{{ $workloadStats['total_weekly_hours'] ?? 0 }} hrs</p>
        <p class="tich-text tich-mt-2">{{ $workloadStats['over_capacity_count'] ?? 0 }} over capacity</p>
    </article>
</div>

<div class="tich-grid tich-grid--2 tich-mt-6" style="gap: 1.5rem; align-items: start;">
    <article class="tich-card">
        <h3 class="tich-h4">Quick actions</h3>
        <ul class="tich-mt-4" style="margin: 0; padding-left: 1.25rem;">
            <li class="tich-text"><a href="{{ route('departments.academics.programs.index', ['department' => $department->getRouteKey()]) }}" class="tich-link">Programme curriculum builder</a></li>
            <li class="tich-text tich-mt-2"><a href="{{ route('departments.academics.units.index', ['department' => $department->getRouteKey()]) }}" class="tich-link">Manage unit catalog</a></li>
            <li class="tich-text tich-mt-2"><a href="{{ route('departments.academics.workload.index', ['department' => $department->getRouteKey()]) }}" class="tich-link">Workload allocation</a></li>
            <li class="tich-text tich-mt-2"><a href="{{ route('departments.academics.lesson-plans.index', ['department' => $department->getRouteKey()]) }}" class="tich-link">Lesson plan review</a></li>
        </ul>
    </article>

    <article class="tich-card">
        <h3 class="tich-h4">My teaching load</h3>
        <p class="tich-text tich-mt-4">You have {{ $portalData['allocation_count'] ?? 0 }} unit allocation(s) this semester.</p>
        <p class="tich-caption tich-mt-2">Go to "My units" to view your teaching schedule.</p>
    </article>
</div>
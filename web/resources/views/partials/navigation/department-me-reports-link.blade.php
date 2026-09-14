{{-- Module M&E quarterly reports link. Pass module key, e.g. hr, finance, ict. --}}
@php
    $reportsModule = $module ?? null;
    $reportsRoute = $reportsModule === 'academics'
        ? 'departments.academics.me-reports.index'
        : ($reportsModule ? $reportsModule.'.me-reports.index' : null);
    $reportsActive = $reportsModule === 'academics'
        ? 'departments.academics.me-reports.*'
        : ($reportsModule ? $reportsModule.'.me-reports.*' : null);
@endphp
@if ($reportsRoute && \Illuminate\Support\Facades\Route::has($reportsRoute))
    @php
        $isActive = $reportsActive
            ? request()->routeIs($reportsActive)
            : false;
        if ($reportsModule === 'monitoring_evaluation') {
            $isActive = $isActive || request()->routeIs('monitoring_evaluation.department.*');
        }
    @endphp
    @include('partials.navigation.sidebar-link', [
        'href' => route($reportsRoute),
        'label' => $label ?? 'M&E quarterly reports',
        'icon' => $icon ?? 'bar-chart',
        'active' => $isActive,
        'badgeKey' => $badgeKey ?? null,
        'badge' => $badge ?? null,
    ])
@endif

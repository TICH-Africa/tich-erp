{{-- Module technical plan sidebar link. Pass module key, e.g. hr, finance, ict. --}}
@php
    $planModule = $module ?? null;
    $routes = $planModule
        ? app(\App\Services\DepartmentBudgetingService::class)->technicalPlanRouteNames($planModule)
        : null;
    $planRoute = $routes['index'] ?? null;
    $activePattern = $planModule ? $planModule.'.technical-plans.*' : null;
@endphp
@if ($planRoute && \Illuminate\Support\Facades\Route::has($planRoute))
    @include('partials.navigation.sidebar-link', [
        'href' => route($planRoute),
        'label' => $label ?? 'Technical plan',
        'icon' => $icon ?? 'file-text',
        'active' => $activePattern ? request()->routeIs($activePattern) : false,
        'badge' => $badge ?? null,
    ])
@endif

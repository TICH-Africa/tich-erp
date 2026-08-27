{{-- Module budgeting sidebar link. Pass module key, e.g. hr, finance, ict. --}}
@php
    $budgetModule = $module ?? null;
    $routes = $budgetModule
        ? app(\App\Services\DepartmentBudgetingService::class)->routeNames($budgetModule)
        : null;
    $budgetRoute = $routes['index'] ?? null;
    $activePattern = $budgetModule === 'finance'
        ? 'finance.budget-requests.*'
        : ($budgetModule ? $budgetModule.'.budgeting.*' : null);

    $badge = $badge ?? null;
    if ($badge === null && $budgetModule && \Illuminate\Support\Facades\Schema::hasTable('admin_planning_cycles')) {
        $openCycles = \App\Models\Administration\PlanningCycle::query()->where('status', 'open')->count();
        if ($openCycles > 0) {
            $badge = $openCycles . ' open';
        }
    }
@endphp
@if ($budgetRoute && \Illuminate\Support\Facades\Route::has($budgetRoute))
    @include('partials.navigation.sidebar-link', [
        'href' => route($budgetRoute),
        'label' => $label ?? ($budgetModule === 'finance' ? 'Budget requests' : 'Budgeting'),
        'icon' => $icon ?? 'wallet',
        'active' => $activePattern ? request()->routeIs($activePattern) : false,
        'badge' => $badge,
    ])
@endif

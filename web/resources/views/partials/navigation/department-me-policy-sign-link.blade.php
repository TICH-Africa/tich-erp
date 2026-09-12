{{-- Module M&E policy sign-off link. Pass module key, e.g. hr, finance, ict. --}}
@php
    $policyModule = $module ?? null;
    $signRoute = $policyModule ? $policyModule.'.me-policy.sign' : null;
@endphp
@if ($signRoute && \Illuminate\Support\Facades\Route::has($signRoute))
    @include('partials.navigation.sidebar-link', [
        'href' => route($signRoute),
        'label' => $label ?? 'Sign M&E policy',
        'icon' => $icon ?? 'shield',
        'active' => request()->routeIs($policyModule.'.me-policy.sign*'),
        'badge' => $badge ?? null,
    ])
@endif

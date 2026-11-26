{{-- Module financial policy sign-off link. Pass module key, e.g. hr, finance, ict. --}}
@php
    $policyModule = $module ?? null;
    $signRoute = $policyModule ? $policyModule.'.finance-policy.sign' : null;
@endphp
@if ($signRoute && \Illuminate\Support\Facades\Route::has($signRoute))
    @include('partials.navigation.sidebar-link', [
        'href' => route($signRoute),
        'label' => 'Sign financial policy',
        'icon' => 'shield',
        'active' => request()->routeIs($policyModule.'.finance-policy.sign*'),
    ])
@endif

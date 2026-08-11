@php($financeNav = app(\App\Services\Finance\FinanceNavigationService::class))

<aside class="tich-admin-sidebar" id="finance-admin-sidebar">
    <p class="tich-admin-sidebar__title">Finance Module</p>
    <nav class="tich-admin-sidebar__nav" aria-label="Finance module navigation">
        @include('partials.navigation.sidebar-link', ['href' => route('finance.dashboard'), 'label' => 'Dashboard', 'icon' => 'dashboard', 'active' => request()->routeIs('finance.dashboard')])

        @foreach ($financeNav->sidebarGroups() as $group)
            @include('partials.navigation.sidebar-group', [
                'label' => $group['label'],
                'icon' => $group['icon'],
                'open' => $group['open'],
                'active' => $group['active'],
                'items' => $group['items'],
            ])
        @endforeach
    </nav>
    <div class="tich-admin-sidebar__footer">
        @include('partials.navigation.sidebar-link', ['href' => route('dashboard'), 'label' => 'Back to dashboard', 'icon' => 'arrow-left', 'muted' => true])
    </div>
</aside>

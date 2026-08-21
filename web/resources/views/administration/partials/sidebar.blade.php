@php($adminNav = app(\App\Services\Administration\AdministrationNavigationService::class))

<aside class="tich-admin-sidebar" id="administration-admin-sidebar">
    @include('partials.navigation.sidebar-user')
    <p class="tich-admin-sidebar__title">Administration</p>
    <nav class="tich-admin-sidebar__nav" aria-label="Administration module navigation">
        @include('partials.navigation.sidebar-link', [
            'href' => route('administration.dashboard'),
            'label' => 'Dashboard',
            'icon' => 'dashboard',
            'active' => request()->routeIs('administration.dashboard'),
        ])
        @include('partials.navigation.department-budgeting-link', ['module' => 'administration'])

        @foreach ($adminNav->sidebarGroups() as $group)
            @include('partials.navigation.sidebar-group', [
                'label' => $group['label'],
                'icon' => $group['icon'],
                'open' => $group['open'],
                'active' => $group['active'],
                'badgeKey' => $group['badgeKey'] ?? null,
                'items' => $group['items'],
            ])
        @endforeach
    </nav>
    <div class="tich-admin-sidebar__footer">
        @include('partials.navigation.sidebar-link', ['href' => route('dashboard'), 'label' => 'Back to dashboard', 'icon' => 'arrow-left', 'muted' => true])
    </div>
</aside>

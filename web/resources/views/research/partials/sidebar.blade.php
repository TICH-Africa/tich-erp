<aside class="tich-admin-sidebar" id="research-admin-sidebar">
    @include('partials.navigation.sidebar-user')
    <p class="tich-admin-sidebar__title">Research</p>
    <nav class="tich-admin-sidebar__nav" aria-label="Research module navigation">
        @include('partials.navigation.sidebar-link', ['href' => route('research.dashboard'), 'label' => 'Dashboard', 'icon' => 'dashboard', 'active' => request()->routeIs('research.*')])
        @include('partials.navigation.department-budgeting-link', ['module' => 'research'])
    </nav>
    <div class="tich-admin-sidebar__footer">
        @include('partials.navigation.sidebar-link', ['href' => route('dashboard'), 'label' => 'Back to dashboard', 'icon' => 'arrow-left', 'muted' => true])
    </div>
</aside>

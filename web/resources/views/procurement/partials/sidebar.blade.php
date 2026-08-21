<aside class="tich-admin-sidebar" id="procurement-admin-sidebar">
    @include('partials.navigation.sidebar-user')
    <p class="tich-admin-sidebar__title">Procurement</p>
    <nav class="tich-admin-sidebar__nav" aria-label="Procurement module navigation">
        @include('partials.navigation.sidebar-link', ['href' => route('procurement.dashboard'), 'label' => 'Dashboard', 'icon' => 'dashboard', 'active' => request()->routeIs('procurement.*')])
        @include('partials.navigation.department-budgeting-link', ['module' => 'procurement'])
    </nav>
    <div class="tich-admin-sidebar__footer">
        @include('partials.navigation.sidebar-link', ['href' => route('dashboard'), 'label' => 'Back to dashboard', 'icon' => 'arrow-left', 'muted' => true])
    </div>
</aside>

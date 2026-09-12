<aside class="tich-admin-sidebar" id="procurement-admin-sidebar">
    @include('partials.navigation.sidebar-user')
    <p class="tich-admin-sidebar__title">Procurement</p>
    <nav class="tich-admin-sidebar__nav" aria-label="Procurement module navigation">
        @include('partials.navigation.sidebar-link', ['href' => route('procurement.dashboard'), 'label' => 'Dashboard', 'icon' => 'dashboard', 'active' => request()->routeIs('procurement.dashboard')])
        @include('partials.navigation.sidebar-link', [
            'href' => route('procurement.qa.tasks.index'),
            'label' => 'QA assessment tasks',
            'icon' => 'layers',
            'active' => request()->routeIs('procurement.qa.tasks.*'),
            'badgeKey' => 'qa.tasks',
        ])
        @include('partials.navigation.department-budgeting-link', ['module' => 'procurement'])
        @include('partials.navigation.department-me-policy-sign-link', ['module' => 'procurement'])
    </nav>
    <div class="tich-admin-sidebar__footer">
        @include('partials.navigation.sidebar-link', ['href' => route('employee.dashboard'), 'label' => 'Back to my employee portal', 'icon' => 'arrow-left', 'muted' => true])
    </div>
</aside>

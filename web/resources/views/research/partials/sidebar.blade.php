<aside class="tich-admin-sidebar" id="research-admin-sidebar">
    @include('partials.navigation.sidebar-user')
    <p class="tich-admin-sidebar__title">Research</p>
    <nav class="tich-admin-sidebar__nav" aria-label="Research module navigation">
        @include('partials.navigation.sidebar-link', ['href' => route('research.dashboard'), 'label' => 'Dashboard', 'icon' => 'dashboard', 'active' => request()->routeIs('research.dashboard')])
        @include('partials.navigation.sidebar-link', [
            'href' => route('research.activities.index'),
            'label' => 'Research activities',
            'icon' => 'layers',
            'active' => request()->routeIs('research.activities.*'),
        ])
        @include('partials.navigation.sidebar-link', [
            'href' => route('research.partnerships.index'),
            'label' => 'Partnership inquiries',
            'icon' => 'users',
            'active' => request()->routeIs('research.partnerships.*'),
        ])
        @include('partials.navigation.sidebar-link', [
            'href' => route('research.qa.tasks.index'),
            'label' => 'QA assessment tasks',
            'icon' => 'layers',
            'active' => request()->routeIs('research.qa.tasks.*'),
            'badgeKey' => 'qa.tasks',
        ])
        @include('partials.navigation.department-budgeting-link', ['module' => 'research'])
        @include('partials.navigation.department-technical-plan-link', ['module' => 'research'])
        @include('partials.navigation.department-finance-policy-sign-link', ['module' => 'research'])
        @include('partials.navigation.department-me-reports-link', ['module' => 'research'])
    </nav>
    <div class="tich-admin-sidebar__footer">
        @include('partials.navigation.sidebar-link', ['href' => route('employee.dashboard'), 'label' => 'Back to my employee portal', 'icon' => 'arrow-left', 'muted' => true])
    </div>
</aside>

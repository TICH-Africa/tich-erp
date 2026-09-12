<aside class="tich-admin-sidebar" id="qa-admin-sidebar">
    @include('partials.navigation.sidebar-user')
    <p class="tich-admin-sidebar__title">Quality Assurance</p>
    <nav class="tich-admin-sidebar__nav" aria-label="Quality assurance module navigation">
        @can('qa.read')
            @include('partials.navigation.sidebar-link', ['href' => route('qa.dashboard'), 'label' => 'Command center', 'icon' => 'dashboard', 'active' => request()->routeIs('qa.dashboard')])
            @include('partials.navigation.sidebar-link', ['href' => route('qa.assessments.index'), 'label' => 'Assessment sheets', 'icon' => 'file-text', 'active' => request()->routeIs('qa.assessments.*'), 'badgeKey' => 'assessments'])
            @include('partials.navigation.sidebar-link', ['href' => route('qa.corrective-actions.index'), 'label' => 'Corrective actions', 'icon' => 'shield', 'active' => request()->routeIs('qa.corrective-actions.*'), 'badgeKey' => 'corrective-actions'])
            @include('partials.navigation.sidebar-link', ['href' => route('qa.qca-flags.index'), 'label' => 'QCA flags', 'icon' => 'alert-triangle', 'active' => request()->routeIs('qa.qca-flags.*')])
            @include('partials.navigation.sidebar-link', ['href' => route('qa.capacity.index'), 'label' => 'Capacity building', 'icon' => 'users', 'active' => request()->routeIs('qa.capacity.*')])
            @include('partials.navigation.department-budgeting-link', ['module' => 'qa'])
            @include('partials.navigation.department-me-policy-sign-link', ['module' => 'qa'])
        @endcan
        @include('partials.navigation.sidebar-link', ['href' => route('qa.tasks.index'), 'label' => 'My department tasks', 'icon' => 'layers', 'active' => request()->routeIs('qa.tasks.*'), 'badgeKey' => 'tasks'])
    </nav>
    <div class="tich-admin-sidebar__footer">
        @include('partials.navigation.sidebar-link', ['href' => route('employee.dashboard'), 'label' => 'Back to my employee portal', 'icon' => 'arrow-left', 'muted' => true])
    </div>
</aside>

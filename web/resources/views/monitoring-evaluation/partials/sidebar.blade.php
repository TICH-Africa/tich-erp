<aside class="tich-admin-sidebar" id="monitoring-evaluation-admin-sidebar">
    @include('partials.navigation.sidebar-user')
    <p class="tich-admin-sidebar__title">Monitoring &amp; evaluation</p>
    <nav class="tich-admin-sidebar__nav" aria-label="Monitoring and evaluation module navigation">
        @can('monitoring_evaluation.read')
            @include('partials.navigation.sidebar-link', ['href' => route('monitoring_evaluation.dashboard'), 'label' => 'Command center', 'icon' => 'dashboard', 'active' => request()->routeIs('monitoring_evaluation.dashboard')])
            @include('partials.navigation.sidebar-link', ['href' => route('monitoring_evaluation.policies.index'), 'label' => 'M&E policy portal', 'icon' => 'file-text', 'active' => request()->routeIs('monitoring_evaluation.policies.*'), 'badgeKey' => 'policies'])
            @include('partials.navigation.sidebar-link', ['href' => route('monitoring_evaluation.plans.index'), 'label' => 'Technical plans', 'icon' => 'layers', 'active' => request()->routeIs('monitoring_evaluation.plans.*'), 'badgeKey' => 'plans'])
            @include('partials.navigation.sidebar-link', ['href' => route('monitoring_evaluation.reports.index'), 'label' => 'Quarterly reports', 'icon' => 'file-text', 'active' => request()->routeIs('monitoring_evaluation.reports.*'), 'badgeKey' => 'reports'])
            @include('partials.navigation.sidebar-link', ['href' => route('monitoring_evaluation.pime.index'), 'label' => 'PIME workspace', 'icon' => 'bar-chart', 'active' => request()->routeIs('monitoring_evaluation.pime.*')])
            @include('partials.navigation.department-budgeting-link', ['module' => 'monitoring_evaluation'])
        @endcan
        @include('partials.navigation.sidebar-link', ['href' => route('monitoring_evaluation.policy.sign'), 'label' => 'Sign M&E policy', 'icon' => 'shield', 'active' => request()->routeIs('monitoring_evaluation.policy.sign*'), 'badgeKey' => 'policy.sign'])
        @include('partials.navigation.sidebar-link', ['href' => route('monitoring_evaluation.department.index'), 'label' => 'My department reports', 'icon' => 'layers', 'active' => request()->routeIs('monitoring_evaluation.department.*'), 'badgeKey' => 'department'])
    </nav>
    <div class="tich-admin-sidebar__footer">
        @include('partials.navigation.sidebar-link', ['href' => route('employee.dashboard'), 'label' => 'Back to my employee portal', 'icon' => 'arrow-left', 'muted' => true])
    </div>
</aside>

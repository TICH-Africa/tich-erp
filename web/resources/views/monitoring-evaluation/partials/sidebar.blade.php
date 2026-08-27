<aside class="tich-admin-sidebar" id="monitoring-evaluation-admin-sidebar">
    @include('partials.navigation.sidebar-user')
    <p class="tich-admin-sidebar__title">Monitoring &amp; evaluation</p>
    <nav class="tich-admin-sidebar__nav" aria-label="Monitoring and evaluation module navigation">
        @include('partials.navigation.sidebar-link', ['href' => route('monitoring_evaluation.dashboard'), 'label' => 'Dashboard', 'icon' => 'dashboard', 'active' => request()->routeIs('monitoring_evaluation.*')])
        @include('partials.navigation.department-budgeting-link', ['module' => 'monitoring_evaluation'])
    </nav>
    <div class="tich-admin-sidebar__footer">
        @include('partials.navigation.sidebar-link', ['href' => route('employee.dashboard'), 'label' => 'Back to my employee portal', 'icon' => 'arrow-left', 'muted' => true])
    </div>
</aside>

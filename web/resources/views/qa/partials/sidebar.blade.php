<aside class="tich-admin-sidebar" id="qa-admin-sidebar">
    @include('partials.navigation.sidebar-user')
    <p class="tich-admin-sidebar__title">Quality Assurance</p>
    <nav class="tich-admin-sidebar__nav" aria-label="Quality assurance module navigation">
        @can('qa.read')
            @include('partials.navigation.sidebar-link', ['href' => route('qa.dashboard'), 'label' => 'Command center', 'icon' => 'dashboard', 'active' => request()->routeIs('qa.dashboard')])
            @include('partials.navigation.sidebar-link', ['href' => route('qa.assessments.index'), 'label' => 'IQA assessments', 'icon' => 'file-text', 'active' => request()->routeIs('qa.assessments.*'), 'badgeKey' => 'assessments'])
            @include('partials.navigation.sidebar-link', ['href' => route('qa.lesson-plans.index'), 'label' => 'Lesson plans', 'icon' => 'notebook', 'active' => request()->routeIs('qa.lesson-plans.*')])
            @include('partials.navigation.sidebar-link', ['href' => route('qa.workplans.index'), 'label' => 'Workplans', 'icon' => 'clipboard-list', 'active' => request()->routeIs('qa.workplans.*')])
            @include('partials.navigation.department-budgeting-link', ['module' => 'qa'])
            @include('partials.navigation.department-technical-plan-link', ['module' => 'qa'])
            @include('partials.navigation.department-finance-policy-sign-link', ['module' => 'qa'])
            @include('partials.navigation.department-me-reports-link', ['module' => 'qa'])
        @endcan
    </nav>
    <div class="tich-admin-sidebar__footer">
        @include('partials.navigation.sidebar-link', ['href' => route('employee.dashboard'), 'label' => 'Back to my employee portal', 'icon' => 'arrow-left', 'muted' => true])
    </div>
</aside>

@php($financeNav = app(\App\Services\Finance\FinanceNavigationService::class))

<aside class="tich-admin-sidebar" id="finance-admin-sidebar">
    @include('partials.navigation.sidebar-user')
    <p class="tich-admin-sidebar__title">Finance Module</p>
    <nav class="tich-admin-sidebar__nav" aria-label="Finance module navigation">
        @include('partials.navigation.sidebar-link', ['href' => route('finance.dashboard'), 'label' => 'Dashboard', 'icon' => 'dashboard', 'active' => request()->routeIs('finance.dashboard')])
        @include('partials.navigation.sidebar-link', [
            'href' => route('finance.qa.tasks.index'),
            'label' => 'QA assessment tasks',
            'icon' => 'layers',
            'active' => request()->routeIs('finance.qa.tasks.*'),
            'badgeKey' => 'qa.tasks',
        ])
        @include('partials.navigation.department-budgeting-link', ['module' => 'finance'])
        @include('partials.navigation.department-technical-plan-link', ['module' => 'finance'])
        @include('partials.navigation.sidebar-link', [
            'href' => route('finance.financial-policies.index'),
            'label' => 'Financial Policy',
            'icon' => 'file-text',
            'active' => request()->routeIs('finance.financial-policies.*'),
        ])
        @include('partials.navigation.department-finance-policy-sign-link', ['module' => 'finance'])
        @include('partials.navigation.department-me-reports-link', ['module' => 'finance'])

        @foreach ($financeNav->sidebarGroups() as $group)
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
        @include('partials.navigation.sidebar-link', ['href' => route('employee.dashboard'), 'label' => 'Back to my employee portal', 'icon' => 'arrow-left', 'muted' => true])
    </div>
</aside>

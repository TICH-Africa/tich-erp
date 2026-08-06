<aside class="tich-admin-sidebar" id="hr-admin-sidebar">
    <p class="tich-admin-sidebar__title">HR Module</p>
    <nav class="tich-admin-sidebar__nav" aria-label="HR module navigation">
        @include('partials.navigation.sidebar-link', ['href' => route('hr.dashboard'), 'label' => 'Dashboard', 'icon' => 'dashboard', 'active' => request()->routeIs('hr.dashboard')])
        @include('partials.navigation.sidebar-link', ['href' => route('hr.staff.index'), 'label' => 'Staff Directory', 'icon' => 'users', 'active' => request()->routeIs('hr.staff.*')])
        @include('partials.navigation.sidebar-link', [
            'href' => route('hr.onboarding.index'),
            'label' => 'Onboarding',
            'icon' => 'user-plus',
            'active' => request()->routeIs('hr.onboarding.*'),
            'badgeKey' => 'onboarding',
        ])
        @include('partials.navigation.sidebar-link', [
            'href' => route('hr.contracts.index'),
            'label' => 'Contracts',
            'icon' => 'file-text',
            'active' => request()->routeIs('hr.contracts.*'),
            'badgeKey' => 'contracts',
        ])
        @include('partials.navigation.sidebar-link', ['href' => route('hr.vacancies.index'), 'label' => 'Vacancies', 'icon' => 'briefcase', 'active' => request()->routeIs('hr.vacancies.*')])
        @include('partials.navigation.sidebar-link', [
            'href' => route('hr.recruitment.index'),
            'label' => 'Recruitment',
            'icon' => 'user-search',
            'active' => request()->routeIs('hr.recruitment.*'),
            'badgeKey' => 'recruitment',
        ])

        @php
            $leaveRoutesActive = request()->routeIs('hr.leave.*');
        @endphp

        @include('partials.navigation.sidebar-group', [
            'label' => 'Leave',
            'icon' => 'calendar-off',
            'open' => $leaveRoutesActive,
            'active' => $leaveRoutesActive,
            'badgeKey' => 'leave',
            'items' => [
                [
                    'href' => route('hr.leave.index'),
                    'label' => 'Leave requests',
                    'icon' => 'clipboard-list',
                    'active' => request()->routeIs('hr.leave.index') || request()->routeIs('hr.leave.show'),
                    'badgeKey' => 'leave.requests',
                ],
                [
                    'href' => route('hr.leave.overview'),
                    'label' => 'Leave overview',
                    'icon' => 'clipboard-check',
                    'active' => request()->routeIs('hr.leave.overview'),
                ],
                [
                    'href' => route('hr.leave.employees'),
                    'label' => 'All employees',
                    'icon' => 'users',
                    'active' => request()->routeIs('hr.leave.employees'),
                ],
            ],
        ])

        @include('partials.navigation.sidebar-link', ['href' => route('hr.payroll.index'), 'label' => 'Payroll', 'icon' => 'wallet', 'active' => request()->routeIs('hr.payroll.*')])
        @include('partials.navigation.sidebar-link', ['href' => route('hr.policies.index'), 'label' => 'HR Policies', 'icon' => 'shield-check', 'active' => request()->routeIs('hr.policies.*')])
        @include('partials.navigation.sidebar-link', [
            'href' => route('hr.documents.index'),
            'label' => 'Staff Documents',
            'icon' => 'folder',
            'active' => request()->routeIs('hr.documents.index') || request()->routeIs('hr.documents.show') || request()->routeIs('hr.staff.documents.*'),
            'badgeKey' => 'documents',
        ])
        @include('partials.navigation.sidebar-link', ['href' => route('hr.documents.templates.index'), 'label' => 'Document Templates', 'icon' => 'files', 'active' => request()->routeIs('hr.documents.templates.*')])
        @include('partials.navigation.sidebar-link', [
            'href' => route('hr.offboarding.index'),
            'label' => 'Offboarding',
            'icon' => 'log-out',
            'active' => request()->routeIs('hr.offboarding.*'),
            'badgeKey' => 'offboarding',
        ])
        @include('partials.navigation.sidebar-link', ['href' => route('hr.training.index'), 'label' => 'Training', 'icon' => 'presentation', 'active' => request()->routeIs('hr.training.*')])
    </nav>
    <div class="tich-admin-sidebar__footer">
        @include('partials.navigation.sidebar-link', ['href' => route('dashboard'), 'label' => 'Back to dashboard', 'icon' => 'arrow-left', 'muted' => true])
    </div>
</aside>

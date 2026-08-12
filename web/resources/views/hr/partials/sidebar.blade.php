<aside class="tich-admin-sidebar" id="hr-admin-sidebar">
    @include('partials.navigation.sidebar-user')
    <p class="tich-admin-sidebar__title">HR Module</p>
    <nav class="tich-admin-sidebar__nav" aria-label="HR module navigation">
        @include('partials.navigation.sidebar-link', ['href' => route('hr.dashboard'), 'label' => 'Dashboard', 'icon' => 'dashboard', 'active' => request()->routeIs('hr.dashboard')])
        @include('partials.navigation.sidebar-link', ['href' => route('hr.staff.index'), 'label' => 'Staff Directory', 'icon' => 'users', 'active' => request()->routeIs('hr.staff.*')])
        @include('partials.navigation.sidebar-link', [
            'href' => route('hr.profile-changes.index'),
            'label' => 'Profile changes',
            'icon' => 'clipboard-check',
            'active' => request()->routeIs('hr.profile-changes.*'),
            'badgeKey' => 'profile-changes',
        ])
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
        @can('finance.read')
            @include('partials.navigation.sidebar-link', ['href' => route('finance.employee.index'), 'label' => 'Finance: Employee', 'icon' => 'briefcase', 'active' => request()->routeIs('finance.employee.*', 'finance.payroll-integration.*'), 'muted' => true])
        @endcan
        @include('partials.navigation.sidebar-link', [
            'href' => route('hr.policies.index'),
            'label' => 'HR Policies',
            'icon' => 'shield-check',
            'active' => request()->routeIs('hr.policies.*'),
            'badgeKey' => 'policies',
        ])
        @include('partials.navigation.sidebar-link', [
            'href' => route('hr.documents.index'),
            'label' => 'Staff Documents',
            'icon' => 'folder',
            'active' => request()->routeIs('hr.documents.index') || request()->routeIs('hr.documents.show') || request()->routeIs('hr.staff.documents.*'),
            'badgeKey' => 'documents',
        ])
        @include('partials.navigation.sidebar-link', [
            'href' => route('hr.offboarding.index'),
            'label' => 'Offboarding',
            'icon' => 'log-out',
            'active' => request()->routeIs('hr.offboarding.*'),
            'badgeKey' => 'offboarding',
        ])
        @include('partials.navigation.sidebar-link', ['href' => route('hr.training.index'), 'label' => 'Training', 'icon' => 'presentation', 'active' => request()->routeIs('hr.training.*')])

        @php
            $erRoutesActive = request()->routeIs('hr.employee-relations.*');
        @endphp

        @include('partials.navigation.sidebar-group', [
            'label' => 'Employee Relations',
            'icon' => 'users',
            'open' => $erRoutesActive,
            'active' => $erRoutesActive,
            'badgeKey' => 'employee-relations',
            'items' => [
                [
                    'href' => route('hr.employee-relations.disciplinary.index'),
                    'label' => 'Disciplinary',
                    'icon' => 'alert-triangle',
                    'active' => request()->routeIs('hr.employee-relations.disciplinary.*'),
                ],
                [
                    'href' => route('hr.employee-relations.grievances.index'),
                    'label' => 'Grievances',
                    'icon' => 'message-square',
                    'active' => request()->routeIs('hr.employee-relations.grievances.*'),
                    'badgeKey' => 'grievances',
                ],
                [
                    'href' => route('hr.employee-relations.feedback.index'),
                    'label' => 'Feedback',
                    'icon' => 'feedback',
                    'active' => request()->routeIs('hr.employee-relations.feedback.*'),
                    'badgeKey' => 'feedback',
                ],
            ],
        ])
    </nav>
    <div class="tich-admin-sidebar__footer">
        @include('partials.navigation.sidebar-link', ['href' => route('dashboard'), 'label' => 'Back to dashboard', 'icon' => 'arrow-left', 'muted' => true])
    </div>
</aside>

<aside class="tich-admin-sidebar" id="employee-admin-sidebar">
    @include('partials.navigation.sidebar-user')
    <p class="tich-admin-sidebar__title">My Employee Portal</p>
    <p class="tich-caption">{{ $staff->employee_number ?? '' }}</p>

    <nav class="tich-admin-sidebar__nav" aria-label="Employee portal navigation">
        @include('partials.navigation.sidebar-link', [
            'href' => route('employee.profile.edit'),
            'label' => ($mustCompleteProfile ?? false) ? 'Complete profile' : 'Update profile',
            'icon' => 'clipboard-list',
            'active' => request()->routeIs('employee.profile.*'),
            'badgeKey' => ($mustCompleteProfile ?? false) ? null : 'profile-changes',
        ])

        @unless ($mustCompleteProfile ?? false)
            @include('partials.navigation.sidebar-link', ['href' => route('employee.dashboard'), 'label' => 'My profile', 'icon' => 'user', 'active' => request()->routeIs('employee.dashboard')])
            @include('partials.navigation.sidebar-link', [
                'href' => route('employee.leave.index'),
                'label' => 'Apply for leave',
                'icon' => 'calendar-off',
                'active' => request()->routeIs('employee.leave.*'),
                'badgeKey' => 'leave.returned',
            ])
            @include('partials.navigation.sidebar-link', ['href' => route('employee.attendance.index'), 'label' => 'Clock in / out', 'icon' => 'clock', 'active' => request()->routeIs('employee.attendance.*')])
            @include('partials.navigation.sidebar-link', [
                'href' => route('employee.concerns.index'),
                'label' => 'Concerns & issues',
                'icon' => 'shield',
                'active' => request()->routeIs('employee.concerns.*'),
                'badgeKey' => 'concerns',
            ])
            @include('partials.navigation.sidebar-link', [
                'href' => route('employee.relations.feedback.index'),
                'label' => 'My feedback',
                'icon' => 'notebook',
                'active' => request()->routeIs('employee.relations.feedback.*'),
                'badgeKey' => 'feedback',
            ])
            @include('partials.navigation.sidebar-link', [
                'href' => route('policies.assigned'),
                'label' => 'HR Policies',
                'icon' => 'shield-check',
                'active' => request()->routeIs('policies.*'),
                'badgeKey' => 'policies',
            ])

            @if (auth()->user()->isTeachingStaff())
                @include('partials.navigation.sidebar-link', ['href' => route('staff.dashboard'), 'label' => 'Staff portal', 'icon' => 'book-open'])
            @endif
        @endunless
    </nav>
    <div class="tich-admin-sidebar__footer">
        @unless ($mustCompleteProfile ?? false)
            @php
                $dashboardRoute = match (true) {
                    auth()->user()->hasRole('Super Admin') => route('dashboard'),
                    auth()->user()->hasPermission('finance.read') => route('finance.dashboard'),
                    auth()->user()->hasPermission('hr.read') => route('hr.dashboard'),
                    auth()->user()->hasPermission('administration.read') => route('administration.applications.index'),
                    auth()->user()->hasPermission('academics.read') => route('departments.academics.applications.index'),
                    auth()->user()->hasPermission('admissions.read') => route('administration.applications.index'),
                    auth()->user()->hasPermission('academics.read') => route('departments.academics.dashboard'),
                    auth()->user()->hasPermission('research.read') => route('research.dashboard'),
                    auth()->user()->hasPermission('qa.read') => route('qa.dashboard'),
                    auth()->user()->hasPermission('procurement.read') => route('procurement.dashboard'),
                    default => route('dashboard'),
                };
            @endphp
            @include('partials.navigation.sidebar-link', ['href' => $dashboardRoute, 'label' => 'Main dashboard', 'icon' => 'home', 'muted' => true])
        @endunless
    </div>
</aside>

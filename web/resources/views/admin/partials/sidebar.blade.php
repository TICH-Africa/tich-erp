<aside class="tich-admin-sidebar" id="admin-platform-sidebar">
    @include('partials.navigation.sidebar-user')
    <p class="tich-admin-sidebar__title">Platform admin</p>
    <nav class="tich-admin-sidebar__nav" aria-label="Platform admin navigation">
        @include('partials.navigation.sidebar-link', ['href' => route('admin.index'), 'label' => 'Overview', 'icon' => 'dashboard', 'active' => request()->routeIs('admin.index')])
        @can('campuses.manage')
            @include('partials.navigation.sidebar-link', [
                'href' => route('admin.campuses.index'),
                'label' => 'Campuses',
                'icon' => 'building',
                'active' => request()->routeIs('admin.campuses.*'),
                'badgeKey' => 'campuses',
            ])
        @endcan
        @can('departments.manage')
            @include('partials.navigation.sidebar-link', ['href' => route('admin.department-groups.index'), 'label' => 'Department groups', 'icon' => 'layers', 'active' => request()->routeIs('admin.department-groups.*')])
            @include('partials.navigation.sidebar-link', [
                'href' => route('admin.departments.index'),
                'label' => 'Departments',
                'icon' => 'building-2',
                'active' => request()->routeIs('admin.departments.*'),
                'badgeKey' => 'departments',
            ])
        @endcan
        @can('programs.manage')
            @include('partials.navigation.sidebar-link', [
                'href' => route('admin.programs.index'),
                'label' => 'Programmes & courses',
                'icon' => 'book-open',
                'active' => request()->routeIs('admin.programs.*'),
                'badgeKey' => 'programs',
            ])
        @endcan
        @can('users.access.manage')
            @include('partials.navigation.sidebar-link', [
                'href' => route('admin.users.index'),
                'label' => 'Users & access',
                'icon' => 'users',
                'active' => request()->routeIs('admin.users.*'),
                'badgeKey' => 'users',
            ])
            @include('partials.navigation.sidebar-link', ['href' => route('admin.roles.index'), 'label' => 'User roles', 'icon' => 'shield', 'active' => request()->routeIs('admin.roles.*', 'admin.role-categories.*')])
        @endcan
        @can('students.read')
            @include('partials.navigation.sidebar-link', ['href' => route('sis.students.index'), 'label' => 'Student records (SIS)', 'icon' => 'graduation-cap', 'active' => request()->routeIs('sis.students.*')])
        @endcan
        @can('audit_logs.read')
            @include('partials.navigation.sidebar-link', [
                'href' => route('admin.audit-logs.index'),
                'label' => 'Audit logs',
                'icon' => 'scroll',
                'active' => request()->routeIs('admin.audit-logs.*'),
                'badgeKey' => 'audit-logs',
            ])
        @endcan
    </nav>
    <div class="tich-admin-sidebar__footer">
        @include('partials.navigation.sidebar-link', ['href' => route('dashboard'), 'label' => 'Back to dashboard', 'icon' => 'arrow-left', 'muted' => true])
    </div>
</aside>

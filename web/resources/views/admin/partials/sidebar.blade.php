<aside class="tich-admin-sidebar">
    <p class="tich-admin-sidebar__title">Platform admin</p>
    <nav class="tich-admin-sidebar__nav">
        <a href="{{ route('admin.index') }}" class="{{ request()->routeIs('admin.index') ? 'is-active' : '' }}">Overview</a>
        @can('campuses.manage')
            <a href="{{ route('admin.campuses.index') }}" class="{{ request()->routeIs('admin.campuses.*') ? 'is-active' : '' }}">Campuses</a>
        @endcan
        @can('departments.manage')
            <a href="{{ route('admin.department-groups.index') }}" class="{{ request()->routeIs('admin.department-groups.*') ? 'is-active' : '' }}">Department groups</a>
            <a href="{{ route('admin.departments.index') }}" class="{{ request()->routeIs('admin.departments.*') ? 'is-active' : '' }}">Departments</a>
        @endcan
        @can('programs.manage')
            <a href="{{ route('admin.programs.index') }}" class="{{ request()->routeIs('admin.programs.*') ? 'is-active' : '' }}">Programmes &amp; courses</a>
        @endcan
        @can('users.access.manage')
            <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}">Users &amp; access</a>
        @endcan
        @can('audit_logs.read')
            <a href="{{ route('admin.audit-logs.index') }}">Audit logs</a>
        @endcan
        <a href="{{ route('dashboard') }}">← Back to dashboard</a>
    </nav>
</aside>

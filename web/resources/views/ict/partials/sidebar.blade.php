<aside class="tich-admin-sidebar" id="ict-admin-sidebar">
    @include('partials.navigation.sidebar-user')
    <p class="tich-admin-sidebar__title">ICT</p>
    <nav class="tich-admin-sidebar__nav" aria-label="ICT module navigation">
        @include('partials.navigation.sidebar-link', ['href' => route('ict.dashboard'), 'label' => 'Dashboard', 'icon' => 'dashboard', 'active' => request()->routeIs('ict.dashboard')])
        @include('partials.navigation.department-budgeting-link', ['module' => 'ict'])
        @include('partials.navigation.sidebar-link', ['href' => route('ict.registration-invites.index'), 'label' => 'ERP registration invites', 'icon' => 'mail', 'active' => request()->routeIs('ict.registration-invites.*')])
        @can('users.access.manage')
            @include('partials.navigation.sidebar-link', ['href' => route('ict.users.index'), 'label' => 'Users & access', 'icon' => 'users', 'active' => request()->routeIs('ict.users.*')])
            @include('partials.navigation.sidebar-link', ['href' => route('ict.roles.index'), 'label' => 'User roles', 'icon' => 'shield', 'active' => request()->routeIs('ict.roles.*', 'ict.role-categories.*')])
        @endcan
    </nav>
    <div class="tich-admin-sidebar__footer">
        @include('partials.navigation.sidebar-link', ['href' => route('dashboard'), 'label' => 'Back to dashboard', 'icon' => 'arrow-left', 'muted' => true])
    </div>
</aside>

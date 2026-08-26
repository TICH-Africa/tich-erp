<aside class="tich-admin-sidebar" id="ict-admin-sidebar">
    @include('partials.navigation.sidebar-user')
    <p class="tich-admin-sidebar__title">ICT</p>
    <nav class="tich-admin-sidebar__nav" aria-label="ICT module navigation">
        @include('partials.navigation.sidebar-link', ['href' => route('ict.dashboard'), 'label' => 'Dashboard', 'icon' => 'dashboard', 'active' => request()->routeIs('ict.dashboard')])
        @include('partials.navigation.department-budgeting-link', ['module' => 'ict'])

        <p class="tich-admin-sidebar__title tich-mt-4">Website content</p>
        @include('partials.navigation.sidebar-link', ['href' => route('ict.content.about.index'), 'label' => 'About Us', 'icon' => 'book-open', 'active' => request()->routeIs('ict.content.about.*')])
        @include('partials.navigation.sidebar-link', ['href' => route('ict.content.blogs.index'), 'label' => 'Blogs', 'icon' => 'layers', 'active' => request()->routeIs('ict.content.blogs.*')])
        @include('partials.navigation.sidebar-link', ['href' => route('ict.content.events.index'), 'label' => 'Events', 'icon' => 'calendar', 'active' => request()->routeIs('ict.content.events.*')])
        @include('partials.navigation.sidebar-link', ['href' => route('ict.content.courses.index'), 'label' => 'Courses', 'icon' => 'graduation-cap', 'active' => request()->routeIs('ict.content.courses.*')])

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

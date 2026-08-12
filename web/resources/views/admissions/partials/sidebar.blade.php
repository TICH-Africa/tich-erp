<aside class="tich-admin-sidebar">
    @include('partials.navigation.sidebar-user')
    <p class="tich-admin-sidebar__title">Approval dashboard</p>
    <nav class="tich-admin-sidebar__nav">
        @include('partials.navigation.sidebar-link', ['href' => route('admissions.dashboard'), 'label' => 'Overview', 'icon' => 'dashboard', 'active' => request()->routeIs('admissions.dashboard')])
        @include('partials.navigation.sidebar-link', ['href' => route('admissions.applications.index'), 'label' => 'All applications', 'icon' => 'clipboard-list', 'active' => request()->routeIs('admissions.applications.*')])
        @include('partials.navigation.sidebar-link', ['href' => route('admissions.applications.index', ['status' => 'pending']), 'label' => 'Pending review', 'icon' => 'clipboard-check', 'active' => request('status') === 'pending'])
        @can('admin.access')
            @include('partials.navigation.sidebar-link', ['href' => route('admin.index'), 'label' => 'Platform admin', 'icon' => 'shield'])
        @endcan
    </nav>
</aside>

<aside class="tich-admin-sidebar">
    <p class="tich-admin-sidebar__title">Approval dashboard</p>
    <nav class="tich-admin-sidebar__nav">
        <a href="{{ route('admissions.dashboard') }}" class="{{ request()->routeIs('admissions.dashboard') ? 'is-active' : '' }}">Overview</a>
        <a href="{{ route('admissions.applications.index') }}" class="{{ request()->routeIs('admissions.applications.*') ? 'is-active' : '' }}">All applications</a>
        <a href="{{ route('admissions.applications.index', ['status' => 'pending']) }}" class="{{ request('status') === 'pending' ? 'is-active' : '' }}">Pending review</a>
        @can('admin.access')
            <a href="{{ route('admin.index') }}">Platform admin</a>
        @endcan
        <a href="{{ route('dashboard') }}">← Main dashboard</a>
    </nav>
</aside>

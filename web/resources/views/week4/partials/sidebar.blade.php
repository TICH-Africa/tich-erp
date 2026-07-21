<aside class="tich-admin-sidebar">
    <p class="tich-admin-sidebar__title">Approval dashboard</p>
    <nav class="tich-admin-sidebar__nav">
        <a href="{{ route('week4.dashboard') }}" class="{{ request()->routeIs('week4.dashboard') ? 'is-active' : '' }}">Overview</a>
        <a href="{{ route('week4.applications.list') }}" class="{{ request()->routeIs('week4.applications.*') || request()->routeIs('week4.application.*') ? 'is-active' : '' }}">All applications</a>
        <a href="{{ route('week4.applications.list', ['status' => 'pending']) }}" class="{{ request('status') === 'pending' ? 'is-active' : '' }}">Pending review</a>
        @can('admin.access')
            <a href="{{ route('admin.index') }}">Platform admin</a>
        @endcan
        <a href="{{ route('dashboard') }}">← Main dashboard</a>
    </nav>
</aside>

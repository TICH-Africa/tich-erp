<aside class="tich-admin-sidebar">
    <p class="tich-admin-sidebar__title">Academics</p>
    <nav class="tich-admin-sidebar__nav">
        @can('academics.read')
            <a href="{{ route('academics.dashboard') }}" class="{{ request()->routeIs('academics.dashboard') ? 'is-active' : '' }}">Curriculum hub</a>
            <a href="{{ route('academics.departments.index') }}" class="{{ request()->routeIs('academics.departments.*') ? 'is-active' : '' }}">Departments</a>
            <a href="{{ route('academics.units.index') }}" class="{{ request()->routeIs('academics.units.*') ? 'is-active' : '' }}">Unit catalog</a>
            <a href="{{ route('academics.programs.index') }}" class="{{ request()->routeIs('academics.programs.*') ? 'is-active' : '' }}">Programme curriculum</a>
        @endcan
        @can('academics.calendar')
            <a href="{{ route('academics.calendar.index') }}" class="{{ request()->routeIs('academics.calendar.*') ? 'is-active' : '' }}">Academic calendar</a>
        @endcan
        <a href="{{ route('dashboard') }}">← Back to dashboard</a>
    </nav>
</aside>

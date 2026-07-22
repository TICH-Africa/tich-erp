@php($hub = ['department' => $department->id])

<aside class="tich-admin-sidebar">
    <p class="tich-admin-sidebar__title">{{ $department->dept_name }}</p>
    <p class="tich-caption" style="padding: 0 1rem 0.75rem;">Academics &amp; curriculum</p>
    <nav class="tich-admin-sidebar__nav">
        @can('academics.read')
            <a href="{{ route('departments.academics.dashboard', $hub) }}" class="{{ request()->routeIs('departments.academics.dashboard') ? 'is-active' : '' }}">Overview</a>
            <a href="{{ route('departments.academics.departments.index', $hub) }}" class="{{ request()->routeIs('departments.academics.departments.*') ? 'is-active' : '' }}">Learning departments</a>
            <a href="{{ route('departments.academics.units.index', $hub) }}" class="{{ request()->routeIs('departments.academics.units.*') ? 'is-active' : '' }}">Unit catalog</a>
            <a href="{{ route('departments.academics.programs.index', $hub) }}" class="{{ request()->routeIs('departments.academics.programs.*') ? 'is-active' : '' }}">Programme curriculum</a>
        @endcan
        @can('academics.calendar')
            <a href="{{ route('departments.academics.calendar.index', $hub) }}" class="{{ request()->routeIs('departments.academics.calendar.*') ? 'is-active' : '' }}">Academic calendar</a>
        @endcan
        <a href="{{ route('departments.show', $department) }}">← {{ $department->dept_name }} hub</a>
        <a href="{{ route('dashboard') }}">← Main dashboard</a>
    </nav>
</aside>

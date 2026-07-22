<aside class="tich-admin-sidebar">
    <p class="tich-admin-sidebar__title">{{ $department->dept_name }}</p>
    <p class="tich-caption" style="margin: -0.5rem 0 1rem;">Academics &amp; curriculum</p>

    <nav class="tich-admin-sidebar__nav">
        @php($hub = ['department' => $department->id])

        @can('academics.read')
            <p class="tich-admin-sidebar__section">Curriculum</p>
            <a href="{{ route('departments.academics.dashboard', $hub) }}" @class(['is-active' => request()->routeIs('departments.academics.dashboard')])>Overview</a>
            <a href="{{ route('departments.academics.departments.index', $hub) }}" @class(['is-active' => request()->routeIs('departments.academics.departments.*')])>Learning departments</a>
            <a href="{{ route('departments.academics.units.index', $hub) }}" @class(['is-active' => request()->routeIs('departments.academics.units.*')])>Unit catalog</a>
            <a href="{{ route('departments.academics.programs.index', $hub) }}" @class(['is-active' => request()->routeIs('departments.academics.programs.*')])>Programme curriculum</a>
        @endcan

        @can('academics.calendar')
            <a href="{{ route('departments.academics.calendar.index', $hub) }}" @class(['is-active' => request()->routeIs('departments.academics.calendar.*')])>Academic calendar</a>
        @endcan

        <p class="tich-admin-sidebar__section">Navigation</p>
        <a href="{{ route('departments.show', $department) }}">{{ $department->dept_name }} hub</a>
        <a href="{{ route('dashboard') }}">Main dashboard</a>
    </nav>
</aside>

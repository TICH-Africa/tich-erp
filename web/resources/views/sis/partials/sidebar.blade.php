<aside class="tich-admin-sidebar">
    <p class="tich-admin-sidebar__title">Student Information System</p>
    <nav class="tich-admin-sidebar__nav">
        @can('students.read')
            <a href="{{ route('sis.students.index') }}" class="{{ request()->routeIs('sis.students.*') ? 'is-active' : '' }}">Student records</a>
        @endcan
        <a href="{{ route('dashboard') }}">← Back to dashboard</a>
    </nav>
</aside>

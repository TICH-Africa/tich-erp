<aside class="tich-admin-sidebar">
    <p class="tich-admin-sidebar__title">My Employee Portal</p>
    <p class="tich-caption" style="margin: -0.5rem 0 1rem;">{{ $staff->employee_number ?? '' }}</p>

    <nav class="tich-admin-sidebar__nav">
        <a href="{{ route('employee.dashboard') }}" class="{{ request()->routeIs('employee.dashboard') ? 'is-active' : '' }}">My profile</a>
        <a href="{{ route('employee.leave.index') }}" class="{{ request()->routeIs('employee.leave.*') ? 'is-active' : '' }}">Apply for leave</a>
        <a href="{{ route('employee.attendance.index') }}" class="{{ request()->routeIs('employee.attendance.*') ? 'is-active' : '' }}">Clock in / out</a>
        @if (auth()->user()->isTeachingStaff())
            <a href="{{ route('staff.dashboard') }}">Staff portal</a>
        @endif
        @if (auth()->user()->hasPermission('hr.staff.view'))
            <a href="{{ route('hr.dashboard') }}">HR dashboard</a>
        @endif
        <a href="{{ route('dashboard') }}">Main dashboard</a>
    </nav>
</aside>

<aside class="tich-admin-sidebar">
    <p class="tich-admin-sidebar__title">My Employee Portal</p>
    <p class="tich-caption">{{ $staff->employee_number ?? '' }}</p>

    <nav class="tich-admin-sidebar__nav">
        @include('partials.navigation.sidebar-link', ['href' => route('employee.dashboard'), 'label' => 'My profile', 'icon' => 'user', 'active' => request()->routeIs('employee.dashboard')])
        @include('partials.navigation.sidebar-link', ['href' => route('employee.leave.index'), 'label' => 'Apply for leave', 'icon' => 'calendar-off', 'active' => request()->routeIs('employee.leave.*')])
        @include('partials.navigation.sidebar-link', ['href' => route('employee.attendance.index'), 'label' => 'Clock in / out', 'icon' => 'clock', 'active' => request()->routeIs('employee.attendance.*')])
        @if (auth()->user()->isTeachingStaff())
            @include('partials.navigation.sidebar-link', ['href' => route('staff.dashboard'), 'label' => 'Staff portal', 'icon' => 'book-open'])
        @endif
        @if (auth()->user()->hasPermission('hr.staff.view'))
            @include('partials.navigation.sidebar-link', ['href' => route('hr.dashboard'), 'label' => 'HR dashboard', 'icon' => 'users'])
        @endif
    </nav>
    <div class="tich-admin-sidebar__footer">
        @include('partials.navigation.sidebar-link', ['href' => route('dashboard'), 'label' => 'Main dashboard', 'icon' => 'home', 'muted' => true])
    </div>
</aside>

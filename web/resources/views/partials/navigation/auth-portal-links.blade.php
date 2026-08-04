@if (auth()->user()->hasEmployeeProfile())
    <a href="{{ route('employee.dashboard') }}" class="tich-nav__link{{ request()->routeIs('employee.*') ? ' is-active' : '' }}">My Employee Portal</a>
@endif
@if (auth()->user()->isEnrolledStudent())
    <a href="{{ route('portal.dashboard') }}" class="tich-nav__link{{ request()->routeIs('portal.*') ? ' is-active' : '' }}">Student portal</a>
@elseif (auth()->user()->isTeachingStaff())
    <a href="{{ route('staff.dashboard') }}" class="tich-nav__link{{ request()->routeIs('staff.*') ? ' is-active' : '' }}">Staff portal</a>
@else
    <a href="{{ route('dashboard') }}" class="tich-nav__link{{ request()->routeIs('dashboard') ? ' is-active' : '' }}">Dashboard</a>
@endif

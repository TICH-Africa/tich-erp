@if (auth()->user()->hasEmployeeProfile())
    @unless ($mobile ?? false)
        <div class="tich-nav__item" data-nav-item data-nav-item-pinned>
    @endunless
    @include('partials.navigation.nav-link', [
        'href' => route('employee.dashboard'),
        'label' => 'My Employee Portal',
        'icon' => 'user',
        'active' => request()->routeIs('employee.*'),
        'mobile' => $mobile ?? false,
    ])
    @unless ($mobile ?? false)
        </div>
    @endunless
@endif
@if (auth()->user()->isEnrolledStudent())
    @unless ($mobile ?? false)
        <div class="tich-nav__item" data-nav-item data-nav-item-pinned>
    @endunless
    @include('partials.navigation.nav-link', [
        'href' => route('portal.dashboard'),
        'label' => 'Student portal',
        'icon' => 'graduation-cap',
        'active' => request()->routeIs('portal.*'),
        'mobile' => $mobile ?? false,
    ])
    @unless ($mobile ?? false)
        </div>
    @endunless
@elseif (auth()->user()->isTeachingStaff())
    @unless ($mobile ?? false)
        <div class="tich-nav__item" data-nav-item data-nav-item-pinned>
    @endunless
    @include('partials.navigation.nav-link', [
        'href' => route('staff.dashboard'),
        'label' => 'Staff portal',
        'icon' => 'book-open',
        'active' => request()->routeIs('staff.*'),
        'mobile' => $mobile ?? false,
    ])
    @unless ($mobile ?? false)
        </div>
    @endunless
@else
    @php
        $dashboardRoute = match (true) {
            auth()->user()->hasRole('Super Admin') => route('dashboard'),
            auth()->user()->hasPermission('finance.read') => route('finance.dashboard'),
            auth()->user()->hasPermission('hr.read') => route('hr.dashboard'),
            auth()->user()->hasPermission('admissions.read') => route('admissions.dashboard'),
            auth()->user()->hasPermission('academics.read') => route('departments.academics.dashboard'),
            auth()->user()->hasPermission('research.read') => route('research.dashboard'),
            auth()->user()->hasPermission('qa.read') => route('qa.dashboard'),
            auth()->user()->hasPermission('procurement.read') => route('procurement.dashboard'),
            default => route('dashboard'),
        };
    @endphp
    @unless ($mobile ?? false)
        <div class="tich-nav__item" data-nav-item data-nav-item-pinned>
    @endunless
    @include('partials.navigation.nav-link', [
        'href' => $dashboardRoute,
        'label' => 'Dashboard',
        'icon' => 'dashboard',
        'active' => request()->routeIs('dashboard'),
        'mobile' => $mobile ?? false,
    ])
    @unless ($mobile ?? false)
        </div>
    @endunless
@endif

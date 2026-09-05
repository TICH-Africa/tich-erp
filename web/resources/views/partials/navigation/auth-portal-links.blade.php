@if (auth()->user()->hasEmployeeProfile() && ! auth()->user()->isEnrolledStudent() && ! app(\App\Services\EmployeeAssignmentService::class)->isAwaitingDepartmentAssignment(auth()->user()))
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
        'href' => route('dashboard'),
        'label' => 'Dashboard',
        'icon' => 'dashboard',
        'active' => request()->routeIs('dashboard'),
        'mobile' => $mobile ?? false,
    ])
    @unless ($mobile ?? false)
        </div>
    @endunless
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
    @unless ($mobile ?? false)
        <div class="tich-nav__item" data-nav-item data-nav-item-pinned>
    @endunless
    @include('partials.navigation.nav-link', [
        'href' => route('dashboard'),
        'label' => 'Dashboard',
        'icon' => 'dashboard',
        'active' => request()->routeIs('dashboard'),
        'mobile' => $mobile ?? false,
    ])
    @unless ($mobile ?? false)
        </div>
    @endunless
@endif

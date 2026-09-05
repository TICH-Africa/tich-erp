@php
    use App\Services\EmployeeAssignmentService;

    $mobile = $mobile ?? false;
    $user = auth()->user();
    $assignmentService = app(EmployeeAssignmentService::class);

    $employeeHref = route('login');
    $employeeActive = false;
    if ($user && $user->hasEmployeeProfile() && ! $user->isEnrolledStudent() && ! $assignmentService->isAwaitingDepartmentAssignment($user)) {
        $employeeHref = route('employee.dashboard');
        $employeeActive = request()->routeIs('employee.*');
    }

    $studentHref = route('login');
    $studentActive = false;
    if ($user && $user->isEnrolledStudent()) {
        $studentHref = route('portal.dashboard');
        $studentActive = request()->routeIs('portal.*');
    }

    $staffHref = route('login');
    $staffActive = false;
    if ($user && $user->isTeachingStaff() && ! $user->isEnrolledStudent()) {
        $staffHref = route('staff.dashboard');
        $staffActive = request()->routeIs('staff.*');
    }

    $portalLinks = [];

    if ($user && $user->hasEmployeeProfile() && ! $user->isEnrolledStudent() && ! $assignmentService->isAwaitingDepartmentAssignment($user)) {
        $portalLinks[] = [
            'label' => 'My Employee Portal',
            'href' => $employeeHref,
            'icon' => 'user',
            'active' => $employeeActive,
        ];
    }

    if ($user && $user->isEnrolledStudent()) {
        $portalLinks[] = [
            'label' => 'Student portal',
            'href' => $studentHref,
            'icon' => 'graduation-cap',
            'active' => $studentActive,
        ];
    }

    if ($user && $user->isTeachingStaff() && ! $user->isEnrolledStudent()) {
        $portalLinks[] = [
            'label' => 'Dashboard',
            'href' => route('dashboard'),
            'icon' => 'dashboard',
            'active' => request()->routeIs('dashboard'),
        ];
        $portalLinks[] = [
            'label' => 'Staff portal',
            'href' => $staffHref,
            'icon' => 'book-open',
            'active' => $staffActive,
        ];
    }

    if ($user && ! $user->isEnrolledStudent() && ! $user->isTeachingStaff()) {
        $awaitingAssignment = $user->hasEmployeeProfile()
            && $assignmentService->isAwaitingDepartmentAssignment($user);

        if (! $user->hasEmployeeProfile() || $awaitingAssignment) {
            $portalLinks[] = [
                'label' => 'Dashboard',
                'href' => route('dashboard'),
                'icon' => 'dashboard',
                'active' => request()->routeIs('dashboard'),
            ];
        }
    }
@endphp

@if ($mobile)
    <div class="tich-nav-drawer__section">
        <p class="tich-nav-drawer__section-title">Portals & access</p>
        @foreach ($portalLinks as $portalLink)
            @include('partials.navigation.nav-link', [
                'href' => $portalLink['href'],
                'label' => $portalLink['label'],
                'icon' => $portalLink['icon'],
                'active' => $portalLink['active'],
                'mobile' => true,
            ])
        @endforeach
    </div>
@endif

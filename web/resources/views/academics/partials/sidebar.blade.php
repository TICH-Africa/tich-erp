@if (request()->routeIs('departments.academics.programs.curriculum') && ! empty($curriculumSidebarNavigation) && ! empty($program))
    @include('academics.partials.program-curriculum-sidebar', [
        'program' => $program,
        'curriculumSidebarNavigation' => $curriculumSidebarNavigation,
    ])
@elseif (! empty($learningDepartment))
    @php
        $departmentDashboard = app(\App\Services\DepartmentDashboardService::class);
    @endphp
    @include('departments.partials.sidebar', [
        'department' => $learningDepartment,
        'sidebarNavigation' => $departmentDashboard->sidebarNavigation(auth()->user(), $learningDepartment),
        'categoryLabel' => fn (\App\Models\Department $dept) => $departmentDashboard->categoryLabel($dept),
    ])
@else
    <aside class="tich-admin-sidebar">
        <p class="tich-admin-sidebar__title">{{ $department->dept_name }}</p>
        <p class="tich-caption">Academics &amp; curriculum</p>

        <nav class="tich-admin-sidebar__nav">
            @php
                $hub = ['department' => $department->id];
            @endphp

            @can('academics.read')
                <p class="tich-admin-sidebar__section">Curriculum</p>
                @include('partials.navigation.sidebar-link', ['href' => route('departments.academics.dashboard', $hub), 'label' => 'Overview', 'icon' => 'dashboard', 'active' => request()->routeIs('departments.academics.dashboard')])
                @include('partials.navigation.sidebar-link', ['href' => route('departments.academics.departments.index', $hub), 'label' => 'Learning departments', 'icon' => 'building-2', 'active' => request()->routeIs('departments.academics.departments.*')])
                @include('partials.navigation.sidebar-link', ['href' => route('departments.academics.units.index', $hub), 'label' => 'Unit catalog', 'icon' => 'library', 'active' => request()->routeIs('departments.academics.units.*')])
                @include('partials.navigation.sidebar-link', ['href' => route('departments.academics.programs.index', $hub), 'label' => 'Programme curriculum', 'icon' => 'book-open', 'active' => request()->routeIs('departments.academics.programs.*')])
                @include('partials.navigation.sidebar-link', ['href' => route('departments.academics.attendance-ledger.index', $hub), 'label' => 'Attendance ledger', 'icon' => 'clipboard-check', 'active' => request()->routeIs('departments.academics.attendance-ledger.*')])
                @include('partials.navigation.sidebar-link', ['href' => route('departments.academics.lesson-plans.index', $hub), 'label' => 'Lesson plan approval', 'icon' => 'notebook', 'active' => request()->routeIs('departments.academics.lesson-plans.index') || request()->routeIs('departments.academics.lesson-plans.show')])
                @include('partials.navigation.sidebar-link', ['href' => route('departments.academics.lesson-plans.audit', $hub), 'label' => 'Lesson plan audit', 'icon' => 'search', 'active' => request()->routeIs('departments.academics.lesson-plans.audit')])
                @include('partials.navigation.sidebar-link', ['href' => route('departments.academics.performance.index', $hub), 'label' => 'Performance terminal', 'icon' => 'bar-chart', 'active' => request()->routeIs('departments.academics.performance.*')])
            @endcan

            @can('academics.calendar')
                @include('partials.navigation.sidebar-link', ['href' => route('departments.academics.calendar.index', $hub), 'label' => 'Academic calendar', 'icon' => 'calendar', 'active' => request()->routeIs('departments.academics.calendar.*')])
            @endcan

            <p class="tich-admin-sidebar__section">Navigation</p>
            @include('partials.navigation.sidebar-link', ['href' => route('departments.show', $department), 'label' => $department->dept_name.' hub', 'icon' => 'layout-grid', 'muted' => true])
        </nav>
    </aside>
@endif

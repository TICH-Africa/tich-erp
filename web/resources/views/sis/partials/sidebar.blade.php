<aside class="tich-admin-sidebar">
    <p class="tich-admin-sidebar__title">Student Information System</p>
    <nav class="tich-admin-sidebar__nav">
        @can('students.read')
            @include('partials.navigation.sidebar-link', ['href' => route('sis.students.index'), 'label' => 'Student records', 'icon' => 'graduation-cap', 'active' => request()->routeIs('sis.students.*')])
        @endcan
    </nav>
    <div class="tich-admin-sidebar__footer">
        @include('partials.navigation.sidebar-link', ['href' => route('dashboard'), 'label' => 'Back to dashboard', 'icon' => 'arrow-left', 'muted' => true])
    </div>
</aside>

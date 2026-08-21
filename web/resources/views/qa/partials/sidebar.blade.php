<aside class="tich-admin-sidebar" id="qa-admin-sidebar">
    @include('partials.navigation.sidebar-user')
    <p class="tich-admin-sidebar__title">Quality Assurance</p>
    <nav class="tich-admin-sidebar__nav" aria-label="Quality assurance module navigation">
        @include('partials.navigation.sidebar-link', ['href' => route('qa.dashboard'), 'label' => 'Dashboard', 'icon' => 'dashboard', 'active' => request()->routeIs('qa.*')])
        @include('partials.navigation.department-budgeting-link', ['module' => 'qa'])
    </nav>
    <div class="tich-admin-sidebar__footer">
        @include('partials.navigation.sidebar-link', ['href' => route('dashboard'), 'label' => 'Back to dashboard', 'icon' => 'arrow-left', 'muted' => true])
    </div>
</aside>

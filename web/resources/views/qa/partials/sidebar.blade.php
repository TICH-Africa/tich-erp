<aside class="tich-admin-sidebar" id="qa-admin-sidebar">
    <p class="tich-admin-sidebar__title">Quality Assurance</p>
    <nav class="tich-admin-sidebar__nav" aria-label="Quality assurance module navigation">
        @include('partials.navigation.sidebar-link', ['href' => route('qa.dashboard'), 'label' => 'Dashboard', 'icon' => 'dashboard', 'active' => request()->routeIs('qa.*')])
    </nav>
    <div class="tich-admin-sidebar__footer">
        @include('partials.navigation.sidebar-link', ['href' => route('dashboard'), 'label' => 'Back to dashboard', 'icon' => 'arrow-left', 'muted' => true])
    </div>
</aside>

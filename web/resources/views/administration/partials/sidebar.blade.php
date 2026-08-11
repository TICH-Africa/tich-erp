<aside class="tich-admin-sidebar" id="administration-admin-sidebar">
    <p class="tich-admin-sidebar__title">Administration</p>
    <nav class="tich-admin-sidebar__nav" aria-label="Administration module navigation">
        @include('partials.navigation.sidebar-link', ['href' => route('administration.dashboard'), 'label' => 'Dashboard', 'icon' => 'dashboard', 'active' => request()->routeIs('administration.*')])
    </nav>
    <div class="tich-admin-sidebar__footer">
        @include('partials.navigation.sidebar-link', ['href' => route('dashboard'), 'label' => 'Back to dashboard', 'icon' => 'arrow-left', 'muted' => true])
    </div>
</aside>

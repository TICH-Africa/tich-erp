<aside class="tich-admin-sidebar" id="finance-admin-sidebar">
    <p class="tich-admin-sidebar__title">Finance Module</p>
    <nav class="tich-admin-sidebar__nav" aria-label="Finance module navigation">
        @include('partials.navigation.sidebar-link', [
            'href' => route('finance.dashboard'),
            'label' => 'Dashboard',
            'icon' => 'dashboard',
            'active' => request()->routeIs('finance.dashboard'),
        ])
    </nav>
    <div class="tich-admin-sidebar__footer">
        @include('partials.navigation.sidebar-link', [
            'href' => route('dashboard'),
            'label' => 'Back to dashboard',
            'icon' => 'arrow-left',
            'muted' => true,
        ])
    </div>
</aside>

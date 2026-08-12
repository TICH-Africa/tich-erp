<aside class="tich-admin-sidebar" id="ict-admin-sidebar">
    <p class="tich-admin-sidebar__title">ICT</p>
    <nav class="tich-admin-sidebar__nav" aria-label="ICT module navigation">
        @include('partials.navigation.sidebar-link', ['href' => route('ict.dashboard'), 'label' => 'Dashboard', 'icon' => 'dashboard', 'active' => request()->routeIs('ict.dashboard')])
        @include('partials.navigation.sidebar-link', ['href' => route('ict.registration-invites.index'), 'label' => 'ERP registration invites', 'icon' => 'mail', 'active' => request()->routeIs('ict.registration-invites.*')])
    </nav>
    <div class="tich-admin-sidebar__footer">
        @include('partials.navigation.sidebar-link', ['href' => route('dashboard'), 'label' => 'Back to dashboard', 'icon' => 'arrow-left', 'muted' => true])
    </div>
</aside>

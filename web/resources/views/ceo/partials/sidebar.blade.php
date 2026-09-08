<aside class="tich-admin-sidebar" id="ceo-admin-sidebar">
    @include('partials.navigation.sidebar-user')
    <p class="tich-admin-sidebar__title">Executive office</p>
    <nav class="tich-admin-sidebar__nav" aria-label="CEO navigation">
        @include('partials.navigation.sidebar-link', [
            'href' => route('ceo.dashboard'),
            'label' => 'Overview',
            'icon' => 'dashboard',
            'active' => request()->routeIs('ceo.dashboard'),
        ])
        @include('partials.navigation.sidebar-link', [
            'href' => route('ceo.budgets.index'),
            'label' => 'Budget authorizations',
            'icon' => 'file-text',
            'active' => request()->routeIs('ceo.budgets.*'),
        ])
        @include('partials.navigation.sidebar-link', [
            'href' => route('ceo.approvals.index'),
            'label' => 'Approval workflow',
            'icon' => 'shield',
            'active' => request()->routeIs('ceo.approvals.*'),
        ])
        @include('partials.navigation.sidebar-link', [
            'href' => route('ceo.curriculum.index'),
            'label' => 'Curriculum sign-off',
            'icon' => 'book-open',
            'active' => request()->routeIs('ceo.curriculum.*'),
        ])
        @include('partials.navigation.sidebar-link', [
            'href' => route('ceo.academics.index'),
            'label' => 'Academics hub',
            'icon' => 'graduation-cap',
            'active' => request()->routeIs('ceo.academics.*'),
        ])
        @include('partials.navigation.sidebar-link', [
            'href' => route('ceo.quality.index'),
            'label' => 'Quality reports',
            'icon' => 'shield',
            'active' => request()->routeIs('ceo.quality.*'),
        ])
        @include('partials.navigation.sidebar-link', [
            'href' => route('ceo.me.index'),
            'label' => 'M&E reports',
            'icon' => 'bar-chart',
            'active' => request()->routeIs('ceo.me.*'),
        ])
    </nav>
    <div class="tich-admin-sidebar__footer">
        @include('partials.navigation.sidebar-link', [
            'href' => route('dashboard'),
            'label' => 'Back to main dashboard',
            'icon' => 'arrow-left',
            'muted' => true,
        ])
    </div>
</aside>

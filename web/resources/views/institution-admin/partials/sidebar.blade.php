<aside class="tich-admin-sidebar" id="institution-admin-sidebar">
    @include('partials.navigation.sidebar-user')
    <p class="tich-admin-sidebar__title">Institution administrator</p>
    <p class="tich-caption" style="padding:0 1rem .75rem;opacity:.75;">Read-only oversight</p>
    <nav class="tich-admin-sidebar__nav" aria-label="Institution administrator navigation">
        @include('partials.navigation.sidebar-link', [
            'href' => route('institution-admin.dashboard'),
            'label' => 'Overview',
            'icon' => 'dashboard',
            'active' => request()->routeIs('institution-admin.dashboard'),
        ])
        @include('partials.navigation.sidebar-link', [
            'href' => route('ceo.budgets.index'),
            'label' => 'Budget authorizations',
            'icon' => 'file-text',
            'active' => request()->routeIs('ceo.budgets.*'),
            'badgeKey' => 'budgets',
        ])
        @include('partials.navigation.sidebar-link', [
            'href' => route('ceo.approvals.index'),
            'label' => 'Approval workflow',
            'icon' => 'shield',
            'active' => request()->routeIs('ceo.approvals.*'),
            'badgeKey' => 'approvals',
        ])
        @include('partials.navigation.sidebar-link', [
            'href' => route('ceo.procurement.index'),
            'label' => 'Procurement',
            'icon' => 'shopping-cart',
            'active' => request()->routeIs('ceo.procurement.*'),
            'badgeKey' => 'procurement',
        ])
        @include('partials.navigation.sidebar-link', [
            'href' => route('ceo.curriculum.index'),
            'label' => 'Curriculum sign-off',
            'icon' => 'book-open',
            'active' => request()->routeIs('ceo.curriculum.*'),
            'badgeKey' => 'curriculum',
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
            'badgeKey' => 'quality',
        ])
        @include('partials.navigation.sidebar-link', [
            'href' => route('ceo.me.index'),
            'label' => 'M&E reports',
            'icon' => 'bar-chart',
            'active' => request()->routeIs('ceo.me.*'),
            'badgeKey' => 'me',
        ])
        @include('partials.navigation.sidebar-link', [
            'href' => route('ceo.finance-policy.index'),
            'label' => 'Financial policy',
            'icon' => 'file-text',
            'active' => request()->routeIs('ceo.finance-policy.*'),
            'badgeKey' => 'finance-policy',
        ])
        @include('partials.navigation.sidebar-link', [
            'href' => route('ceo.me-policy.index'),
            'label' => 'M&E policy',
            'icon' => 'file-text',
            'active' => request()->routeIs('ceo.me-policy.*'),
            'badgeKey' => 'me-policy',
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

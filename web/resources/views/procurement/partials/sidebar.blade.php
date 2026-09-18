<aside class="tich-admin-sidebar" id="procurement-admin-sidebar">
    @include('partials.navigation.sidebar-user')
    <p class="tich-admin-sidebar__title">Procurement</p>
    <nav class="tich-admin-sidebar__nav" aria-label="Procurement module navigation">
        @include('partials.navigation.sidebar-link', ['href' => route('procurement.dashboard'), 'label' => 'Dashboard', 'icon' => 'dashboard', 'active' => request()->routeIs('procurement.dashboard')])
        @include('partials.navigation.sidebar-link', [
            'href' => route('procurement.requisitions.index'),
            'label' => 'Requisitions',
            'icon' => 'clipboard-list',
            'active' => request()->routeIs('procurement.requisitions.*'),
            'badgeKey' => 'requisitions.pending',
        ])
        @include('partials.navigation.sidebar-link', [
            'href' => route('procurement.suppliers.index'),
            'label' => 'Suppliers',
            'icon' => 'building-storefront',
            'active' => request()->routeIs('procurement.suppliers.*'),
            'badgeKey' => 'suppliers.pending',
        ])
        @include('partials.navigation.sidebar-link', [
            'href' => route('procurement.rfqs.index'),
            'label' => 'RFQs',
            'icon' => 'clipboard-document-list',
            'active' => request()->routeIs('procurement.rfqs.*'),
            'badgeKey' => 'rfqs.actionable',
        ])
        @include('partials.navigation.sidebar-link', [
            'href' => route('procurement.assets.index'),
            'label' => 'Assets',
            'icon' => 'cpu',
            'active' => request()->routeIs('procurement.assets.*'),
        ])
        @include('partials.navigation.sidebar-link', [
            'href' => route('procurement.grns.index'),
            'label' => 'GRNs',
            'icon' => 'package',
            'active' => request()->routeIs('procurement.grns.*'),
            'badgeKey' => 'grns.pending',
        ])
        @include('partials.navigation.sidebar-link', [
            'href' => route('procurement.inventory-items.index'),
            'label' => 'Inventory',
            'icon' => 'archive',
            'active' => request()->routeIs('procurement.inventory-items.*'),
        ])
        @include('partials.navigation.sidebar-link', [
            'href' => route('procurement.stock-alerts.index'),
            'label' => 'Stock alerts',
            'icon' => 'bell',
            'active' => request()->routeIs('procurement.stock-alerts.*'),
            'badgeKey' => 'stock-alerts.active',
        ])
        @include('partials.navigation.sidebar-link', [
            'href' => route('procurement.asset-disposals.index'),
            'label' => 'Disposals',
            'icon' => 'trash',
            'active' => request()->routeIs('procurement.asset-disposals.*'),
            'badgeKey' => 'asset-disposals.pending',
        ])
        @include('partials.navigation.sidebar-link', [
            'href' => route('procurement.asset-audits.index'),
            'label' => 'Audits',
            'icon' => 'clipboard-check',
            'active' => request()->routeIs('procurement.asset-audits.*'),
            'badgeKey' => 'asset-audits.pending',
        ])
        @include('partials.navigation.sidebar-link', [
            'href' => route('procurement.stock-issues.index'),
            'label' => 'Stock issues',
            'icon' => 'shopping-cart',
            'active' => request()->routeIs('procurement.stock-issues.*'),
            'badgeKey' => 'stock-issues.pending',
        ])
        @include('partials.navigation.sidebar-link', [
            'href' => route('procurement.qa.tasks.index'),
            'label' => 'QA assessment tasks',
            'icon' => 'layers',
            'active' => request()->routeIs('procurement.qa.tasks.*'),
            'badgeKey' => 'qa.tasks',
        ])
        @include('partials.navigation.department-budgeting-link', ['module' => 'procurement'])
        @include('partials.navigation.department-me-policy-sign-link', ['module' => 'procurement'])
        @include('partials.navigation.department-me-reports-link', ['module' => 'procurement'])
    </nav>
    <div class="tich-admin-sidebar__footer">
        @include('partials.navigation.sidebar-link', ['href' => route('employee.dashboard'), 'label' => 'Back to my employee portal', 'icon' => 'arrow-left', 'muted' => true])
    </div>
</aside>

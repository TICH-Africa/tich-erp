<aside class="tich-admin-sidebar" id="finance-admin-sidebar">
    <p class="tich-admin-sidebar__title">Finance Module</p>
    <nav class="tich-admin-sidebar__nav" aria-label="Finance module navigation">
        @include('partials.navigation.sidebar-link', ['href' => route('finance.dashboard'), 'label' => 'Dashboard', 'icon' => 'dashboard', 'active' => request()->routeIs('finance.dashboard')])
        @include('partials.navigation.sidebar-link', ['href' => route('finance.fee-structures.index'), 'label' => 'Fee structures', 'icon' => 'layers', 'active' => request()->routeIs('finance.fee-structures.*')])
        @include('partials.navigation.sidebar-link', ['href' => route('finance.student-accounts.index'), 'label' => 'Student accounts', 'icon' => 'users', 'active' => request()->routeIs('finance.student-accounts.*')])
        @include('partials.navigation.sidebar-link', ['href' => route('finance.invoices.index'), 'label' => 'Invoices', 'icon' => 'file-text', 'active' => request()->routeIs('finance.invoices.*')])
        @include('partials.navigation.sidebar-link', ['href' => route('finance.payments.index'), 'label' => 'Payments', 'icon' => 'wallet', 'active' => request()->routeIs('finance.payments.*')])
        @include('partials.navigation.sidebar-link', ['href' => route('finance.ledger.index'), 'label' => 'General ledger', 'icon' => 'book-open', 'active' => request()->routeIs('finance.ledger.*')])
        @include('partials.navigation.sidebar-link', ['href' => route('finance.reports.index'), 'label' => 'Reports', 'icon' => 'bar-chart', 'active' => request()->routeIs('finance.reports.*')])
    </nav>
    <div class="tich-admin-sidebar__footer">
        @include('partials.navigation.sidebar-link', ['href' => route('dashboard'), 'label' => 'Back to dashboard', 'icon' => 'arrow-left', 'muted' => true])
    </div>
</aside>

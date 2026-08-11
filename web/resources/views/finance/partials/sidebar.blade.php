<aside class="tich-admin-sidebar" id="finance-admin-sidebar">
    <p class="tich-admin-sidebar__title">Finance Module</p>
    <nav class="tich-admin-sidebar__nav" aria-label="Finance module navigation">
        @include('partials.navigation.sidebar-link', ['href' => route('finance.dashboard'), 'label' => 'Dashboard', 'icon' => 'dashboard', 'active' => request()->routeIs('finance.dashboard')])

        @php
            $department = request()->route('department');
            $deptParam = $department ? ['department' => $department->id ?? $department] : [];
            $studentFinanceActive = request()->routeIs('finance.student-finance.*');
            $studentFinanceItems = [];

            if ($department) {
                $studentFinanceItems = [
                    ['href' => route('finance.student-finance.accounts.index', $deptParam), 'label' => 'Student accounts', 'icon' => 'users', 'active' => request()->routeIs('finance.student-finance.accounts.*')],
                    ['href' => route('finance.student-finance.fee-structures.index', $deptParam), 'label' => 'Fee structures', 'icon' => 'layers', 'active' => request()->routeIs('finance.student-finance.fee-structures.*')],
                    ['href' => route('finance.student-finance.invoices.index', $deptParam), 'label' => 'Invoices', 'icon' => 'file-text', 'active' => request()->routeIs('finance.student-finance.invoices.*')],
                    ['href' => route('finance.student-finance.payments.index', $deptParam), 'label' => 'Payments & Receipts', 'icon' => 'wallet', 'active' => request()->routeIs('finance.student-finance.payments.*')],
                    ['href' => route('finance.student-finance.refunds.index', $deptParam), 'label' => 'Refunds', 'icon' => 'refresh-cw', 'active' => request()->routeIs('finance.student-finance.refunds.*')],
                ];
            } else {
                $financeDepartment = \App\Models\Department::query()->where('dept_code', 'FIN')->whereNull('parent_dept_id')->first();

                if ($financeDepartment) {
                    $deptParam = ['department' => $financeDepartment->id];

                    $studentFinanceItems = [
                        ['href' => route('finance.student-finance.accounts.index', $deptParam), 'label' => 'Student accounts', 'icon' => 'users', 'active' => false],
                        ['href' => route('finance.student-finance.fee-structures.index', $deptParam), 'label' => 'Fee structures', 'icon' => 'layers', 'active' => false],
                        ['href' => route('finance.student-finance.invoices.index', $deptParam), 'label' => 'Invoices', 'icon' => 'file-text', 'active' => false],
                        ['href' => route('finance.student-finance.payments.index', $deptParam), 'label' => 'Payments & Receipts', 'icon' => 'wallet', 'active' => false],
                        ['href' => route('finance.student-finance.refunds.index', $deptParam), 'label' => 'Refunds', 'icon' => 'refresh-cw', 'active' => false],
                    ];
                }
            }
        @endphp

        @if ($studentFinanceItems)
            @include('partials.navigation.sidebar-group', [
                'label' => 'Student Finance',
                'icon' => 'users',
                'open' => $studentFinanceActive,
                'active' => $studentFinanceActive,
                'items' => $studentFinanceItems,
            ])
        @endif

        @include('partials.navigation.sidebar-link', ['href' => route('finance.ledger.index'), 'label' => 'General ledger', 'icon' => 'book-open', 'active' => request()->routeIs('finance.ledger.*')])
        @can('finance.payments.manage')
            @include('partials.navigation.sidebar-link', ['href' => route('finance.mpesa.settings'), 'label' => 'M-Pesa settings', 'icon' => 'smartphone', 'active' => request()->routeIs('finance.mpesa.*')])
        @endcan
        @include('partials.navigation.sidebar-link', ['href' => route('finance.reports.index'), 'label' => 'Reports', 'icon' => 'bar-chart', 'active' => request()->routeIs('finance.reports.*')])
    </nav>
    <div class="tich-admin-sidebar__footer">
        @include('partials.navigation.sidebar-link', ['href' => route('dashboard'), 'label' => 'Back to dashboard', 'icon' => 'arrow-left', 'muted' => true])
    </div>
</aside>

@extends('layouts.finance')

@section('title', 'Financial reports')

@section('finance-content')
    @php
        $reportTabs = [
            'trial_balance' => 'Trial balance',
            'balance_sheet' => 'Balance sheet',
            'income_statement' => 'Profit & loss',
            'cashflow' => 'Cashflow',
            'general_ledger' => 'General ledger',
            'ar_aging' => 'AR ageing',
            'ap_aging' => 'AP ageing',
            'payroll_summary' => 'Payroll summary',
            'finance_audit' => 'Finance audit',
            'reconciliation' => 'Reconciliation',
        ];
        $filters = $filters ?? [];
        $period = $filters['period'] ?? ($reportData['period']['preset'] ?? 'mtd');
        $viewMode = $filters['view'] ?? ($reportData['view_mode'] ?? 'standard');
        $from = $filters['from'] ?? ($reportData['period']['from'] ?? now()->startOfMonth()->toDateString());
        $to = $filters['to'] ?? ($reportData['period']['to'] ?? now()->toDateString());
        $queryBase = array_filter([
            'report' => $report,
            'period' => $period,
            'view' => $viewMode,
            'from' => $period === 'custom' ? $from : null,
            'to' => $period === 'custom' ? $to : null,
        ]);
    @endphp

    <x-page-toolbar title="Financial reports" meta="Compliance-ready statements and live treasury dashboards">
        <x-slot:actions>
            <a href="{{ route('finance.reports.view.pdf', $queryBase) }}" class="tich-btn tich-btn-secondary" target="_blank" rel="noopener">View PDF</a>
            <a href="{{ route('finance.reports.view.excel', $queryBase) }}" class="tich-btn tich-btn-secondary" target="_blank" rel="noopener">View XLS</a>
            <a href="{{ route('finance.reports.export.pdf', $queryBase) }}" class="tich-btn tich-btn-secondary">Download PDF</a>
            <a href="{{ route('finance.reports.export.excel', $queryBase) }}" class="tich-btn tich-btn-secondary">Download Excel</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif

    <div class="tich-flex tich-mb-4" style="gap:0.5rem; flex-wrap:wrap;">
        @foreach ($reportTabs as $key => $label)
            <a href="{{ route('finance.reports.index', ['report' => $key]) }}" class="tich-btn {{ $report === $key ? 'tich-btn-primary' : 'tich-btn-ghost' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if ($report === 'income_statement')
        <div class="tich-card tich-mb-4">
            <form method="GET" action="{{ route('finance.reports.index') }}" class="tich-flex tich-flex--wrap tich-gap-2 tich-flex--middle" style="padding:0.75rem 1rem;">
                <input type="hidden" name="report" value="income_statement">
                <label class="tich-caption" for="period">Period</label>
                <select id="period" name="period" class="uf-input" style="width:160px; height:34px;" onchange="this.form.submit()">
                    <option value="mtd" @selected($period === 'mtd')>Month to date</option>
                    <option value="fy" @selected($period === 'fy')>Financial year</option>
                    <option value="custom" @selected($period === 'custom')>Custom From/To</option>
                </select>
                @if ($period === 'custom')
                    <input type="date" name="from" value="{{ $from }}" class="uf-input" style="height:34px;">
                    <input type="date" name="to" value="{{ $to }}" class="uf-input" style="height:34px;">
                    <button type="submit" class="tich-btn tich-btn-primary tich-btn--sm">Apply</button>
                @endif
                <div style="flex:1;"></div>
                <span class="tich-caption">View</span>
                <a href="{{ route('finance.reports.index', array_merge($queryBase, ['view' => 'standard', 'snapshot' => null])) }}"
                   class="tich-btn tich-btn--sm {{ $viewMode === 'standard' ? 'tich-btn-primary' : 'tich-btn-ghost' }}">Standard (P&amp;L)</a>
                <a href="{{ route('finance.reports.index', array_merge($queryBase, ['view' => 'full', 'snapshot' => null])) }}"
                   class="tich-btn tich-btn--sm {{ $viewMode === 'full' ? 'tich-btn-primary' : 'tich-btn-ghost' }}">Full (incl. BS accounts)</a>
            </form>

            <div class="tich-flex tich-flex--wrap tich-gap-2 tich-flex--middle" style="padding:0 1rem 0.75rem;">
                <form method="POST" action="{{ route('finance.reports.profit-loss.snapshots.store') }}" class="tich-flex tich-gap-2 tich-flex--middle">
                    @csrf
                    <input type="hidden" name="period" value="{{ $period }}">
                    <input type="hidden" name="view" value="{{ $viewMode }}">
                    <input type="hidden" name="from" value="{{ $from }}">
                    <input type="hidden" name="to" value="{{ $to }}">
                    <input type="text" name="label" class="uf-input" placeholder="Snapshot label (optional)" style="width:220px; height:34px;">
                    <button type="submit" class="tich-btn tich-btn-secondary tich-btn--sm">Save period snapshot</button>
                </form>
                @if (($snapshots ?? collect())->isNotEmpty())
                    <form method="GET" action="{{ route('finance.reports.index') }}" class="tich-flex tich-gap-2 tich-flex--middle">
                        <input type="hidden" name="report" value="income_statement">
                        <select name="snapshot" class="uf-input" style="width:260px; height:34px;" onchange="this.form.submit()">
                            <option value="">Live report</option>
                            @foreach ($snapshots as $item)
                                <option value="{{ $item->id }}" @selected(($activeSnapshot->id ?? null) === $item->id)>
                                    {{ $item->label }} ({{ $item->period_from->format('Y-m-d') }} → {{ $item->period_to->format('Y-m-d') }})
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif
            </div>
        </div>
    @endif

    @php
        $reportPeriodLabel = $reportData['period_label']
            ?? ('As at '.\Illuminate\Support\Carbon::parse($reportData['as_at'] ?? now())->format('d M Y'));
    @endphp

    <article class="tich-card tich-mb-4">
        <h2 class="tich-h3" style="margin:0;">{{ $reportTitle }}</h2>
        <p class="tich-caption tich-mt-2">
            {{ $reportPeriodLabel }}
            @if (! empty($reportData['entry_count']))
                · {{ number_format($reportData['entry_count']) }} entries
            @endif
            @if (! empty($activeSnapshot))
                · <span class="tich-badge">Saved snapshot</span>
            @endif
        </p>
    </article>

    @includeWhen($report === 'trial_balance', 'finance.reports.partials.trial-balance', ['data' => $reportData])
    @includeWhen($report === 'balance_sheet', 'finance.reports.partials.balance-sheet', ['data' => $reportData])
    @includeWhen($report === 'income_statement', 'finance.reports.partials.income-statement', ['data' => $reportData])
    @includeWhen($report === 'cashflow', 'finance.reports.partials.cashflow', ['data' => $reportData])
    @includeWhen($report === 'general_ledger', 'finance.reports.partials.general-ledger', ['data' => $reportData])
    @includeWhen($report === 'ar_aging', 'finance.reports.partials.ar-aging', ['data' => $reportData])
    @includeWhen($report === 'ap_aging', 'finance.reports.partials.ap-aging', ['data' => $reportData])
    @includeWhen($report === 'payroll_summary', 'finance.reports.partials.payroll-summary', ['data' => $reportData])
    @includeWhen($report === 'finance_audit', 'finance.reports.partials.finance-audit', ['data' => $reportData, 'filters' => $filters ?? []])
    @includeWhen($report === 'reconciliation', 'finance.reports.partials.reconciliation', ['data' => $reportData])
@endsection

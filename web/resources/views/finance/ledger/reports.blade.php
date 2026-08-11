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
        ];
    @endphp

    <x-page-toolbar title="Financial reports" meta="Compliance-ready statements and live treasury dashboards">
        <x-slot:actions>
            <a href="{{ route('finance.reports.view.pdf', ['report' => $report]) }}" class="tich-btn tich-btn-secondary" target="_blank" rel="noopener">View PDF</a>
            <a href="{{ route('finance.reports.view.excel', ['report' => $report]) }}" class="tich-btn tich-btn-secondary" target="_blank" rel="noopener">View XLS</a>
            <a href="{{ route('finance.reports.export.pdf', ['report' => $report]) }}" class="tich-btn tich-btn-secondary">Download PDF</a>
            <a href="{{ route('finance.reports.export.excel', ['report' => $report]) }}" class="tich-btn tich-btn-secondary">Download Excel</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-flex tich-mb-4" style="gap:0.5rem; flex-wrap:wrap;">
        @foreach ($reportTabs as $key => $label)
            <a href="{{ route('finance.reports.index', ['report' => $key]) }}" class="tich-btn {{ $report === $key ? 'tich-btn-primary' : 'tich-btn-ghost' }}">{{ $label }}</a>
        @endforeach
    </div>

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
@endsection

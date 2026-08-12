@extends('layouts.finance')

@section('title', 'Accounts Payable')

@section('finance-content')
    <x-page-toolbar title="{{ $payable->invoice_number }}" meta="Supplier invoice details">
        <x-slot:actions>
            <a href="{{ route('finance.ap.index', $department) }}" class="tich-btn tich-btn-ghost">Back to AP</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--2 tich-mt-6">
        <article class="tich-card">
            <h3 class="tich-h4">Invoice</h3>
            <dl class="tich-mt-4" style="display:grid; gap:0.75rem;">
                <div><dt class="tich-caption">Supplier</dt><dd>{{ $payable->supplier?->supplier_name ?? '—' }}</dd></div>
                <div><dt class="tich-caption">Invoice date</dt><dd>{{ $payable->invoice_date?->format('d M Y') ?? '—' }}</dd></div>
                <div><dt class="tich-caption">Due date</dt><dd>{{ $payable->due_date?->format('d M Y') ?? '—' }}</dd></div>
                <div><dt class="tich-caption">Invoice amount</dt><dd>KES {{ number_format((float) $payable->invoice_amount, 2) }}</dd></div>
                <div><dt class="tich-caption">Tax</dt><dd>KES {{ number_format((float) $payable->tax_amount, 2) }}</dd></div>
                <div><dt class="tich-caption">Total</dt><dd><strong>KES {{ number_format((float) $payable->total_amount, 2) }}</strong></dd></div>
                <div><dt class="tich-caption">Paid</dt><dd>KES {{ number_format((float) $payable->amount_paid, 2) }}</dd></div>
                <div><dt class="tich-caption">Balance</dt><dd><strong>KES {{ number_format((float) $payable->balance, 2) }}</strong></dd></div>
            </dl>
        </article>

        <article class="tich-card">
            <h3 class="tich-h4">Workflow</h3>
            <dl class="tich-mt-4" style="display:grid; gap:0.75rem;">
                <div><dt class="tich-caption">Three-way match</dt><dd>{{ ucfirst(str_replace('_', ' ', $payable->three_way_match_status)) }}</dd></div>
                <div><dt class="tich-caption">Finance approval</dt><dd>{{ ucfirst($payable->finance_approval_status) }}</dd></div>
                <div><dt class="tich-caption">Payment status</dt><dd>{{ ucfirst($payable->payment_status) }}</dd></div>
                @if ($payable->payment_reference)
                    <div><dt class="tich-caption">Payment reference</dt><dd>{{ $payable->payment_reference }}</dd></div>
                @endif
                @if ($payable->purchaseOrder)
                    <div><dt class="tich-caption">Purchase order</dt><dd>{{ $payable->purchaseOrder->po_number }}</dd></div>
                @endif
            </dl>
        </article>
    </div>
@endsection

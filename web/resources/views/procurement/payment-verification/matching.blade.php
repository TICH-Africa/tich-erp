@extends('layouts.procurement')

@section('title', 'Three-way Matching')

@section('procurement-content')
    <x-page-toolbar title="Three-way matching engine" meta="Requisition / PO, quotation, and invoice comparison">
        <x-slot:actions>
            <a href="{{ route('procurement.payment-verification.index') }}" class="tich-btn tich-btn-secondary">Back to overview</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-mt-6">
        <a href="{{ route('procurement.payment-verification.invoices.create') }}" class="tich-btn tich-btn-primary">Create New Invoice</a>
    </div>

    <div class="tich-card tich-mt-6">
        <h2 class="tich-h3">Matching overview</h2>
        <p class="tich-text">The engine compares approved requisition data, supplier quotations, and incoming supplier invoices before any payment is routed.</p>

        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Supplier</th>
                        <th>Amount</th>
                        <th>Match status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $record)
                        <tr>
                            <td>{{ $record->invoice->invoice_number ?? $record->id }}</td>
                            <td>{{ $record->invoice->supplier->supplier_name ?? 'N/A' }}</td>
                            <td>KES {{ number_format($record->total_invoiced, 2) }}</td>
                            <td>
                                @if($record->status === 'full_match')
                                    <span class="tich-status tich-status--success">{{ $record->status }}</span>
                                @elseif($record->status === 'discrepancy')
                                    <span class="tich-status tich-status--danger">{{ $record->status }}</span>
                                @elseif($record->status === 'partial_match')
                                    <span class="tich-status tich-status--warning">{{ $record->status }}</span>
                                @else
                                    <span class="tich-status tich-status--neutral">{{ $record->status }}</span>
                                @endif
                            </td>
                            <td>
                                @if($record->status === 'pending')
                                    <form method="POST" action="{{ route('procurement.payment-verification.matching.run', $record->invoice_id) }}">
                                        @csrf
                                        <button type="submit" class="tich-btn tich-btn-primary tich-btn--sm">Run Match</button>
                                    </form>
                                @elseif($record->status === 'discrepancy')
                                    <a href="{{ route('procurement.payment-verification.discrepancies') }}" class="tich-btn tich-btn--sm tich-btn--warning">Review</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <article class="tich-card tich-mt-6">
        <h3 class="tich-h4">Rules applied</h3>
        <ul class="tich-list tich-mt-3">
            <li>Quantity check: invoiced quantity must not exceed approved purchase quantity (+5% tolerance).</li>
            <li>Price check: invoice unit price must match the approved order or quotation.</li>
            <li>Description check: invoice line items must match the approved item specification.</li>
            <li>Auto-route: fully matched invoices are forwarded to Finance for payment.</li>
        </ul>
    </article>

    @if($purchaseOrders->isNotEmpty())
    <article class="tich-card tich-mt-6">
        <h3 class="tich-h4">Purchase Orders Awaiting Matching</h3>
        <div class="tich-table-wrap tich-mt-3">
            <table class="tich-admin-table">
                <thead>
                    <tr><th>PO Number</th><th>Supplier</th><th>Amount</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @foreach($purchaseOrders as $po)
                        <tr>
                            <td>{{ $po->po_number }}</td>
                            <td>{{ $po->supplier->supplier_name ?? 'N/A' }}</td>
                            <td>KES {{ number_format($po->total_amount, 2) }}</td>
                            <td>{{ $po->status }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </article>
    @endif

    @if(session('success'))
        <div class="tich-alert tich-alert--success tich-mt-6">{{ session('success') }}</div>
    @endif
@endsection

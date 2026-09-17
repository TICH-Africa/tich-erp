@extends('layouts.procurement')

@section('title', 'Create Invoice')

@section('procurement-content')
    <x-page-toolbar title="Create Invoice" meta="Record supplier invoice for three-way matching">
        <x-slot:actions>
            <a href="{{ route('procurement.payment-verification.index') }}" class="tich-btn tich-btn-secondary">Cancel</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-mt-6">
        <form method="POST" action="{{ $action ?? route('procurement.payment-verification.invoices.store') }}">
            @csrf

            <div class="tich-grid tich-grid--2 tich-mt-3">
                <div>
                    <label class="tich-label">Invoice Number *</label>
                    <input type="text" name="invoice_number" class="tich-input" value="{{ old('invoice_number', $invoice?->invoice_number ?? '') }}" required>
                </div>
                <div>
                    <label class="tich-label">Invoice Date *</label>
                    <input type="date" name="invoice_date" class="tich-input" value="{{ old('invoice_date', $invoice?->invoice_date ?? now()->toDateString()) }}" required>
                </div>
            </div>

            <div class="tich-grid tich-grid--2 tich-mt-3">
                <div>
                    <label class="tich-label">Supplier *</label>
                    <select name="supplier_id" class="tich-input" required>
                        <option value="">Select supplier</option>
                        @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ (old('supplier_id', $invoice?->supplier_id ?? '')) == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->supplier_name }}
                        </option>
                    @endforeach
                    </select>
                </div>
                <div>
                    <label class="tich-label">Due Date</label>
                    <input type="date" name="due_date" class="tich-input" value="{{ old('due_date', $invoice?->due_date ?? '') }}">
                </div>
            </div>

            <div class="tich-grid tich-grid--2 tich-mt-3">
                <div>
                    <label class="tich-label">Purchase Order *</label>
                    <select name="purchase_order_id" class="tich-input" required id="po-select">
                        <option value="">Select purchase order</option>
                        @foreach($purchaseOrders as $po)
                            <option value="{{ $po->id }}" {{ (old('purchase_order_id', $invoice?->purchase_order_id ?? '')) == $po->id ? 'selected' : '' }}>
                                {{ $po->po_number }} - {{ $po->total_amount }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="tich-label">RFQ (optional)</label>
                    <select name="rfq_id" class="tich-input" id="rfq-select">
                        <option value="">Select quotation</option>
                        @foreach($rfqs as $rfq)
                            <option value="{{ $rfq->id }}" {{ (old('rfq_id', $invoice?->rfq_id ?? '')) == $rfq->id ? 'selected' : '' }}>
                                {{ $rfq->rfq_number }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="tich-grid tich-grid--3 tich-mt-3">
                <div>
                    <label class="tich-label">Subtotal</label>
                    <input type="number" step="0.01" name="subtotal" class="tich-input" value="{{ old('subtotal', $invoice?->subtotal ?? 0) }}">
                </div>
                <div>
                    <label class="tich-label">Tax Amount</label>
                    <input type="number" step="0.01" name="tax_amount" class="tich-input" value="{{ old('tax_amount', $invoice?->tax_amount ?? 0) }}">
                </div>
                <div>
                    <label class="tich-label">Total Amount *</label>
                    <input type="number" step="0.01" name="total_amount" class="tich-input" value="{{ old('total_amount', $invoice?->total_amount ?? 0) }}" required>
                </div>
            </div>

            <div class="tich-grid tich-grid--2 tich-mt-3">
                <div>
                    <label class="tich-label">Retention / Holdback %</label>
                    <input type="number" step="0.01" max="100" name="retention_percent" class="tich-input" value="{{ old('retention_percent', $invoice?->retention_percent ?? 0) }}">
                </div>
                <div>
                    <label class="tich-label">Notes</label>
                    <input type="text" name="notes" class="tich-input" value="{{ old('notes', $invoice?->notes ?? '') }}">
                </div>
            </div>

            <div class="tich-grid tich-grid--2 tich-mt-6">
                <button type="submit" class="tich-btn tich-btn-primary">Save Invoice</button>
                <a href="{{ route('procurement.payment-verification.index') }}" class="tich-btn tich-btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
@endsection

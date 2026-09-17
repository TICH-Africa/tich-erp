@extends('layouts.procurement')

@section('title', 'M-Pesa STK Push')

@section('procurement-content')
    <x-page-toolbar title="M-Pesa STK push" meta="Supplier payment approvals and Daraja callback handling" />

    @if(session('success'))
        <div class="tich-alert tich-alert--success tich-mt-6">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="tich-alert tich-alert--danger tich-mt-6">{{ session('error') }}</div>
    @endif

    <div class="tich-card tich-mt-6">
        <h2 class="tich-h3">STK Push Queue</h2>
        <p class="tich-text">M-Pesa STK pushes for supplier invoices below KES 70,000. Payables above this threshold use bank transfer.</p>

        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Supplier</th>
                        <th>Phone</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stkPushes as $push)
                        <tr>
                            <td>{{ $push->procurementInvoice?->invoice_number ?? 'N/A' }}</td>
                            <td>{{ $push->procurementInvoice?->supplier?->supplier_name ?? 'N/A' }}</td>
                            <td>{{ $push->phone }}</td>
                            <td>KES {{ number_format($push->amount, 2) }}</td>
                            <td>
                                @if($push->status === 'pending')
                                    <span class="tich-status tich-status--warning">{{ $push->status }}</span>
                                @elseif($push->status === 'completed')
                                    <span class="tich-status tich-status--success">{{ $push->status }}</span>
                                @elseif($push->status === 'failed')
                                    <span class="tich-status tich-status--danger">{{ $push->status }}</span>
                                @else
                                    <span class="tich-status tich-status--neutral">{{ $push->status }}</span>
                                @endif
                            </td>
                            <td>
                                @if($push->status === 'pending')
                                    <form method="POST" action="{{ route('procurement.payment-verification.mpesa-stk.callback') }}" class="tich-inline-form">
                                        @csrf
                                        <input type="hidden" name="checkout_request_id" value="{{ $push->checkout_request_id ?? $push->id }}">
                                        <input type="hidden" name="result_code" value="0">
                                        <input type="hidden" name="result_desc" value="Payment confirmed.">
                                        <button type="submit" class="tich-btn tich-btn--sm tich-btn--success">Confirm</button>
                                    </form>
                                    <form method="POST" action="{{ route('procurement.payment-verification.mpesa-stk.callback') }}" class="tich-inline-form">
                                        @csrf
                                        <input type="hidden" name="checkout_request_id" value="{{ $push->checkout_request_id ?? $push->id }}">
                                        <input type="hidden" name="result_code" value="1">
                                        <input type="hidden" name="result_desc" value="Payment cancelled.">
                                        <button type="submit" class="tich-btn tich-btn--sm tich-btn--secondary">Cancel</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-6">
        <article class="tich-card">
            <h3 class="tich-h4">Planned workflow</h3>
            <ul class="tich-list">
                <li>Finance officer selects M-Pesa for a matched invoice.</li>
                <li>System validates supplier phone number and amount.</li>
                <li>STK push is sent to the supplier's registered phone number.</li>
                <li>Daraja webhook confirms payment and updates the ledger.</li>
            </ul>
        </article>
        <article class="tich-card">
            <h3 class="tich-h4">Operational notes</h3>
            <ul class="tich-list">
                <li>Idempotency checks on transaction ID.</li>
                <li>Duplicate callback rejection and reconciliation job.</li>
                <li>Retention / holdback support for warranty-based payments.</li>
                <li>Invoices above KES 70,000 are routed via bank transfer.</li>
            </ul>
        </article>
    </div>

    <article class="tich-card tich-mt-6">
        <h3 class="tich-h4">Callback Handler</h3>
        <form method="POST" action="{{ route('procurement.payment-verification.mpesa-stk.callback') }}" class="tich-mt-3">
            @csrf
            <div class="tich-grid tich-grid--3 tich-mt-3">
                <div>
                    <label class="tich-label">Checkout Request ID</label>
                    <input type="text" name="checkout_request_id" class="tich-input" placeholder="CheckoutRequestID" required>
                </div>
                <div>
                    <label class="tich-label">Result Code</label>
                    <input type="text" name="result_code" class="tich-input" value="0" required>
                </div>
                <div>
                    <label class="tich-label">Result Description</label>
                    <input type="text" name="result_desc" class="tich-input" placeholder="Payment confirmed" required>
                </div>
            </div>
            <div class="tich-grid tich-grid--2 tich-mt-4">
                <button type="submit" class="tich-btn tich-btn-primary">Process Callback</button>
            </div>
        </form>
    </article>
@endsection

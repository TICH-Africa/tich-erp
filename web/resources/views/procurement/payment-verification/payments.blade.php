@extends('layouts.procurement')

@section('title', 'Finance Routing')

@section('procurement-content')
    <x-page-toolbar title="Finance routing" meta="Matched invoices and payment queue">
        <x-slot:actions>
            <a href="{{ route('procurement.payment-verification.index') }}" class="tich-btn tich-btn-secondary">Back to overview</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if(session('success'))
        <div class="tich-alert tich-alert--success tich-mt-6">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="tich-alert tich-alert--danger tich-mt-6">{{ session('error') }}</div>
    @endif

    <div class="tich-card tich-mt-6">
        <h2 class="tich-h3">Payment queue</h2>
        <p class="tich-text">Once the invoice is fully matched, it routes to Finance with a matching certificate and supporting documents.</p>

        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Supplier</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $payment)
                        <tr>
                            <td>{{ $payment->invoice->invoice_number ?? 'N/A' }}</td>
                            <td>{{ $payment->supplier->supplier_name ?? 'N/A' }}</td>
                            <td>KES {{ number_format($payment->amount, 2) }}</td>
                            <td>{{ $payment->payment_method }}</td>
                            <td>
                                @if($payment->status === 'pending' || $payment->status === 'stk_pending')
                                    <span class="tich-status tich-status--warning">{{ $payment->status }}</span>
                                @elseif($payment->status === 'success')
                                    <span class="tich-status tich-status--success">{{ $payment->status }}</span>
                                @elseif($payment->status === 'failed')
                                    <span class="tich-status tich-status--danger">{{ $payment->status }}</span>
                                @else
                                    <span class="tich-status tich-status--neutral">{{ $payment->status }}</span>
                                @endif
                            </td>
                            <td>
                                @if($payment->status === 'pending')
                                    <form method="POST" action="{{ route('procurement.payment-verification.payments.process') }}" class="tich-inline-form">
                                        @csrf
                                        <input type="hidden" name="invoice_id" value="{{ $payment->invoice_id }}">
                                        <select name="payment_method" class="tich-input tich-input--sm">
                                            <option value="mpesa">M-Pesa</option>
                                            <option value="bank_transfer">Bank Transfer</option>
                                            <option value="cheque">Cheque</option>
                                            <option value="flutterwave">Flutterwave</option>
                                        </select>
                                        <button type="submit" class="tich-btn tich-btn--sm tich-btn-primary">Pay</button>
                                    </form>
                                @elseif($payment->status === 'stk_pending')
                                    <span class="tich-status tich-status--warning">STK Push Sent</span>
                                @endif
                                @if($payment->retention_amount > 0 && $payment->status === 'success')
                                    <form method="POST" action="{{ route('procurement.payment-verification.payments.holdback', $payment->id) }}" class="tich-inline-form tich-mt-1">
                                        @csrf
                                        <input type="number" name="amount" class="tich-input tich-input--sm" placeholder="Release amount" min="0" max="{{ $payment->retention_amount }}">
                                        <button type="submit" class="tich-btn tich-btn--sm tich-btn--secondary">Release Holdback</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <article class="tich-card tich-mt-6">
        <h3 class="tich-h4">Route New Payment</h3>
        <form method="POST" action="{{ route('procurement.payment-verification.payments.process') }}" class="tich-mt-3">
            @csrf
            <div class="tich-grid tich-grid--3 tich-mt-3">
                <div>
                    <label class="tich-label">Invoice</label>
                    <select name="invoice_id" class="tich-input" required>
                        <option value="">Select invoice</option>
                        @foreach($invoices as $invoice)
                            <option value="{{ $invoice->id }}">{{ $invoice->invoice_number }} - {{ $invoice->supplier->supplier_name }} (Balance: KES {{ number_format($invoice->balance, 2) }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="tich-label">Payment Method</label>
                    <select name="payment_method" class="tich-input" required>
                        <option value="mpesa">M-Pesa</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cheque">Cheque</option>
                        <option value="flutterwave">Flutterwave</option>
                    </select>
                </div>
                <div>
                    <label class="tich-label">Reference</label>
                    <input type="text" name="payment_reference" class="tich-input" placeholder="Optional reference">
                </div>
            </div>
            <div class="tich-grid tich-grid--2 tich-mt-4">
                <button type="submit" class="tich-btn tich-btn-primary">Route to Finance</button>
            </div>
        </form>
    </article>
@endsection

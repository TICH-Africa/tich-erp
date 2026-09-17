@extends('layouts.procurement')

@section('title', 'Discrepancy Queue')

@section('procurement-content')
    <x-page-toolbar title="Discrepancy resolution" meta="Supplier queries, exceptions, escalations, and audit notes">
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
        <h2 class="tich-h3">Current discrepancy queue</h2>
        <p class="tich-text">Invoices with mismatched quantity, price, description, or arithmetic totals are held here until a supplier response or management decision is recorded.</p>

        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Supplier</th>
                        <th>Type</th>
                        <th>Deviation</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($discrepancies as $item)
                        <tr>
                            <td>{{ $item->invoice->invoice_number ?? 'N/A' }}</td>
                            <td>{{ $item->supplier->supplier_name ?? 'N/A' }}</td>
                            <td>{{ $item->discrepancy_type }}</td>
                            <td>KES {{ number_format($item->deviation_amount, 2) }}</td>
                            <td>
                                @if($item->status === 'escalated')
                                    <span class="tich-status tich-status--danger">{{ $item->status }}</span>
                                @elseif($item->status === 'awaiting_supplier')
                                    <span class="tich-status tich-status--warning">{{ $item->status }}</span>
                                @elseif($item->status === 'resolved')
                                    <span class="tich-status tich-status--success">{{ $item->status }}</span>
                                @else
                                    <span class="tich-status tich-status--neutral">{{ $item->status }}</span>
                                @endif
                            </td>
                            <td>
                                @if($item->status === 'open' || $item->status === 'awaiting_supplier')
                                    <form method="POST" action="{{ route('procurement.payment-verification.discrepancies.resolve', $item->id) }}" class="tich-inline-form">
                                        @csrf
                                        <input type="hidden" name="resolution_type" value="accepted">
                                        <input type="hidden" name="resolution_note" value="Accepted without changes.">
                                        <button type="submit" class="tich-btn tich-btn--sm tich-btn--success">Accept</button>
                                    </form>
                                    <form method="POST" action="{{ route('procurement.payment-verification.discrepancies.reject', $item->id) }}" class="tich-inline-form">
                                        @csrf
                                        <button type="submit" class="tich-btn tich-btn--sm tich-btn--danger">Reject</button>
                                    </form>
                                    <form method="POST" action="{{ route('procurement.payment-verification.discrepancies.escalate', $item->id) }}" class="tich-inline-form">
                                        @csrf
                                        <button type="submit" class="tich-btn tich-btn--sm tich-btn--warning">Escalate</button>
                                    </form>
                                    <form method="POST" action="{{ route('procurement.payment-verification.discrepancies.flag', $item->id) }}" class="tich-inline-form">
                                        @csrf
                                        <button type="submit" class="tich-btn tich-btn--sm tich-btn--secondary">Flag</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

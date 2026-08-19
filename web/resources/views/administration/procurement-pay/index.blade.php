@extends('layouts.administration')

@section('title', 'Procurement-to-pay')

@section('administration-content')
    <x-page-toolbar title="Procurement-to-pay" meta="End-to-end visibility of supplier vetting, invoice verification, and three-way match validation">
        <x-slot:actions>
            <a href="{{ $procurementUrl }}" class="tich-btn tich-btn-secondary">Procurement module</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-stat-row tich-stat-row--4 tich-mt-8">
        <div class="tich-stat">
            <p class="tich-stat__label">Suppliers</p>
            <p class="tich-stat__value">{{ $snapshot['suppliers'] }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Purchase orders</p>
            <p class="tich-stat__value">{{ $snapshot['purchase_orders'] }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Open AP</p>
            <p class="tich-stat__value">{{ $snapshot['ap_open'] }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Awaiting 3-way match</p>
            <p class="tich-stat__value">{{ $snapshot['three_way_pending'] }}</p>
        </div>
    </div>

    <div class="tich-card tich-table-panel tich-mt-8">
        <h2 class="tich-h3">Recent accounts payable</h2>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Supplier</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>3-way match</th>
                        <th>QuickBooks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($snapshot['recent_ap'] as $ap)
                        <tr>
                            <td><strong>{{ $ap->invoice_number }}</strong></td>
                            <td>{{ $ap->supplier?->supplier_name ?? $ap->supplier?->name ?? '-' }}</td>
                            <td>KES {{ number_format((float) ($ap->total_amount ?? 0), 0) }}</td>
                            <td class="tich-caption">{{ ucfirst($ap->payment_status ?? 'unknown') }}</td>
                            <td class="tich-caption">{{ ucfirst(str_replace('_', ' ', $ap->three_way_match_status ?? 'pending')) }}</td>
                            <td class="tich-caption">{{ ! empty($ap->is_quickbooks_synced) ? 'Synced' : 'Pending' }}</td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 6, 'title' => 'No AP invoices found', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

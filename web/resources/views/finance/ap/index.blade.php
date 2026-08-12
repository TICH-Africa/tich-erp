@extends('layouts.finance')

@section('title', 'Accounts Payable')

@section('finance-content')
    <x-page-toolbar title="Accounts Payable (AP)" meta="Supplier accounts, supplier invoices, verification, approval, payment and the supplier ledger. Uses the three-way match">
        <x-slot:actions>
            <a href="{{ route('finance.ap.create', $department) }}" class="tich-btn tich-btn-primary">+ New invoice</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--3 tich-mt-6">
        <article class="tich-card tich-stat">
            <p class="tich-caption">Total invoices</p>
            <p class="tich-stat__value">{{ number_format($stats['total']) }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Unpaid</p>
            <p class="tich-stat__value">{{ number_format($stats['unpaid']) }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Outstanding balance</p>
            <p class="tich-stat__value">KES {{ number_format($stats['outstanding'], 2) }}</p>
        </article>
    </div>

    <form method="get" class="tich-flex tich-mt-6 tich-mb-4" style="gap:0.5rem; flex-wrap:wrap;">
        <input type="search" name="search" value="{{ $search }}" class="tich-input" placeholder="Search invoice or supplier…">
        <button type="submit" class="tich-btn tich-btn-secondary">Search</button>
    </form>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Supplier</th>
                        <th>Amount</th>
                        <th>Balance</th>
                        <th>Match status</th>
                        <th>Approval</th>
                        <th>Payment</th>
                        <th>Due date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payables as $payable)
                        <tr>
                            <td>
                                <a href="{{ route('finance.ap.show', [$department, $payable]) }}">{{ $payable->invoice_number }}</a>
                            </td>
                            <td>{{ $payable->supplier?->supplier_name ?? '—' }}</td>
                            <td>KES {{ number_format((float) $payable->total_amount, 2) }}</td>
                            <td>KES {{ number_format((float) $payable->balance, 2) }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $payable->three_way_match_status)) }}</td>
                            <td>{{ ucfirst($payable->finance_approval_status) }}</td>
                            <td>{{ ucfirst($payable->payment_status) }}</td>
                            <td>{{ $payable->due_date?->format('d M Y') ?? '—' }}</td>
                            <td>
                                <a href="{{ route('finance.ap.show', [$department, $payable]) }}" class="tich-link">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="tich-table-empty">No AP records yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="tich-mt-4">{{ $payables->links() }}</div>
@endsection

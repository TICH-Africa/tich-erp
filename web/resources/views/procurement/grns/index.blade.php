@extends('layouts.procurement')

@section('title', 'Goods Received Notes')

@section('procurement-content')
    <x-page-toolbar title="GRNs" meta="Goods received against purchase orders">
        <x-slot:actions>
            <a href="{{ route('procurement.grns.create') }}" class="tich-btn tich-btn-primary">+ New GRN</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--4 tich-mt-6">
        <article class="tich-card tich-stat"><p class="tich-caption">Total</p><p class="tich-stat__value">{{ $stats['total'] }}</p></article>
        <article class="tich-card tich-stat"><p class="tich-caption">Pending inspection</p><p class="tich-stat__value">{{ $stats['pending'] }}</p></article>
        <article class="tich-card tich-stat"><p class="tich-caption">Complete</p><p class="tich-stat__value">{{ $stats['complete'] }}</p></article>
        <article class="tich-card tich-stat"><p class="tich-caption">Items</p><p class="tich-stat__value">{{ \App\Models\GrnItem::count() }}</p></article>
    </div>

    <form method="get" class="tich-flex tich-mt-6 tich-mb-4" style="gap:0.5rem; flex-wrap:wrap;">
        <input type="search" name="search" value="{{ $search ?? '' }}" class="tich-input" placeholder="Search GRN or PO number…">
        <select name="status" class="tich-input"><option value="">All statuses</option><option value="pending">Pending</option><option value="complete">Complete</option></select>
        <button type="submit" class="tich-btn tich-btn-secondary">Filter</button>
    </form>

    <div class="tich-card tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead><tr><th>GRN #</th><th>PO</th><th>Supplier</th><th>Received by</th><th>Date</th><th>Inspection</th><th>Items</th><th></th></tr></thead>
                <tbody>
                    @forelse($grns as $grn)
                        <tr>
                            <td><a href="{{ route('procurement.grns.show', $grn) }}" class="tich-link">{{ $grn->grn_number }}</a></td>
                            <td>{{ $grn->purchaseOrder?->po_number ?? '-' }}</td>
                            <td>{{ $grn->purchaseOrder?->supplier?->supplier_name ?? '-' }}</td>
                            <td>{{ $grn->receivedBy?->full_name ?? $grn->receivedBy?->name ?? '-' }}</td>
                            <td>{{ $grn->received_date?->format('d M Y') ?? '-' }}</td>
                            <td>{{ ucfirst($grn->inspection_status) }}</td>
                            <td>{{ $grn->items->count() }}</td>
                            <td><a href="{{ route('procurement.grns.show', $grn) }}" class="tich-link">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="tich-table-empty">No GRNs found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="tich-mt-4">{{ $grns->links() }}</div>
@endsection

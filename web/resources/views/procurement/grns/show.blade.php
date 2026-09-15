@extends('layouts.procurement')

@section('title', 'GRN ' . $grn->grn_number)

@section('procurement-content')
    <x-page-toolbar title="GRN {{ $grn->grn_number }}" meta="{{ $grn->purchaseOrder?->po_number ?? '-' }}">
        <x-slot:actions>
            @if($grn->is_complete && $grn->items->where('status', 'pending')->count() > 0)
                <form method="POST" action="{{ route('procurement.grns.complete', $grn) }}" class="tich-inline-form">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-success">Complete inspection</button>
                </form>
            @endif
            <a href="{{ route('procurement.grns.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--4 tich-mt-6">
        <article class="tich-card tich-stat"><p class="tich-caption">Items</p><p class="tich-stat__value">{{ $grn->items->count() }}</p></article>
        <article class="tich-card tich-stat"><p class="tich-caption">Inspection</p><p class="tich-stat__value">{{ ucfirst($grn->inspection_status) }}</p></article>
        <article class="tich-card tich-stat"><p class="tich-caption">Received</p><p class="tich-stat__value">{{ $grn->received_date?->format('d M Y') ?? '-' }}</p></article>
        <article class="tich-card tich-stat"><p class="tich-caption">Total cost</p><p class="tich-stat__value">KES {{ number_format((float) $grn->items->sum('total_cost'), 2) }}</p></article>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-6" style="gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3" style="margin-top:0;">Receipt</h2>
            <dl class="tich-dl">
                <dt>GRN</dt><dd>{{ $grn->grn_number }}</dd>
                <dt>PO</dt><dd>{{ $grn->purchaseOrder?->po_number ?? '-' }}</dd>
                <dt>Delivery note</dt><dd>{{ $grn->supplier_delivery_note ?? '-' }}</dd>
                <dt>Received by</dt><dd>{{ $grn->receivedBy?->full_name ?? '-' }}</dd>
                <dt>Inspection notes</dt><dd>{{ $grn->inspection_notes ?? '-' }}</dd>
                <dt>Shortages/damages</dt><dd>{{ $grn->shortages_or_damages ?? '-' }}</dd>
            </dl>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3" style="margin-top:0;">Items</h2>
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead><tr><th>Item</th><th>Classification</th><th>Received</th><th>Condition</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($grn->items as $item)
                            <tr>
                                <td>{{ $item->item_name }}</td>
                                <td>{{ ucfirst($item->classification) }}</td>
                                <td>{{ $item->quantity_received }}</td>
                                <td>{{ ucfirst($item->condition) }}</td>
                                <td>{{ ucfirst($item->status) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="tich-table-empty">No items.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </div>
@endsection

@extends('layouts.procurement')

@section('title', 'Item ' . $item->item_name)

@section('procurement-content')
    <x-page-toolbar title="{{ $item->item_name }}" meta="{{ $item->item_code }}">
        <x-slot:actions>
            <a href="{{ route('procurement.inventory-items.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--4 tich-mt-6">
        <article class="tich-card tich-stat"><p class="tich-caption">Current stock</p><p class="tich-stat__value">{{ $item->current_stock }}</p></article>
        <article class="tich-card tich-stat"><p class="tich-caption">Reorder level</p><p class="tich-stat__value">{{ $item->reorder_level }}</p></article>
        <article class="tich-card tich-stat"><p class="tich-caption">Unit cost</p><p class="tich-stat__value">KES {{ number_format((float) $item->unit_cost, 2) }}</p></article>
        <article class="tich-card tich-stat"><p class="tich-caption">Alerts</p><p class="tich-stat__value">{{ $item->alerts->where('status', 'active')->count() }}</p></article>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-6" style="gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3" style="margin-top:0;">Details</h2>
            <dl class="tich-dl">
                <dt>Code</dt><dd>{{ $item->item_code }}</dd>
                <dt>Category</dt><dd>{{ $item->category ?? '-' }}</dd>
                <dt>Unit</dt><dd>{{ $item->unit_of_measure }}</dd>
                <dt>Minimum</dt><dd>{{ $item->minimum_stock }}</dd>
                <dt>Maximum</dt><dd>{{ $item->maximum_stock }}</dd>
                <dt>Location</dt><dd>{{ $item->store_location ?? '-' }}</dd>
                <dt>Supplier</dt><dd>{{ $item->supplier?->supplier_name ?? '-' }}</dd>
            </dl>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3" style="margin-top:0;">Stock ledger</h2>
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead><tr><th>Type</th><th>Qty</th><th>Cost</th><th>Date</th><th>Reference</th><th>Recorded by</th></tr></thead>
                    <tbody>
                        @forelse($item->transactions as $t)
                            <tr>
                                <td>{{ $t->transaction_type }}</td>
                                <td>{{ $t->quantity }}</td>
                                <td>KES {{ number_format((float) $t->total_cost, 2) }}</td>
                                <td>{{ $t->transaction_date?->format('d M Y') ?? '-' }}</td>
                                <td>{{ $t->reference_table ? $t->reference_table . '#' . $t->reference_id : '-' }}</td>
                                <td>{{ $t->recordedBy?->full_name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="tich-table-empty">No transactions.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </div>

    @if($item->alerts->where('status', 'active')->isNotEmpty())
        <article class="tich-card tich-mt-6">
            <h2 class="tich-h3" style="margin-top:0;">Active alerts</h2>
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead><tr><th>Triggered</th><th>Current</th><th>Recommended</th><th>Requisition</th></tr></thead>
                    <tbody>
                        @forelse($item->alerts->where('status', 'active') as $alert)
                            <tr>
                                <td>{{ $alert->triggered_at?->format('d M Y') ?? '-' }}</td>
                                <td>{{ $alert->current_stock }}</td>
                                <td>{{ $alert->recommended_quantity }}</td>
                                <td>{{ $alert->requisition ? \App\Models\ProcurementRequisition::find($alert->requisition_id)?->requisition_number ?? '-' : '-' }}</td>
                            </tr>
                        @empty
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    @endif
@endsection

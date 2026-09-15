@extends('layouts.procurement')

@section('title', 'Inventory')

@section('procurement-content')
    <x-page-toolbar title="Inventory items" meta="Stock ledger, reorder points">
        <x-slot:actions>
            <a href="{{ route('procurement.inventory-items.create') }}" class="tich-btn tich-btn-primary">+ Add item</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--4 tich-mt-6">
        <article class="tich-card tich-stat"><p class="tich-caption">Total items</p><p class="tich-stat__value">{{ $stats['total'] }}</p></article>
        <article class="tich-card tich-stat"><p class="tich-caption">Low stock</p><p class="tich-stat__value">{{ $stats['low_stock'] }}</p></article>
        <article class="tich-card tich-stat"><p class="tich-caption">Active</p><p class="tich-stat__value">{{ $stats['active'] }}</p></article>
        <article class="tich-card tich-stat"><p class="tich-caption">Transactions</p><p class="tich-stat__value">{{ \App\Models\InventoryTransaction::count() }}</p></article>
    </div>

    <form method="get" class="tich-flex tich-mt-6 tich-mb-4" style="gap:0.5rem; flex-wrap:wrap;">
        <input type="search" name="search" value="{{ $search ?? '' }}" class="tich-input" placeholder="Search item name or code…">
        <select name="category" class="tich-input"><option value="">All categories</option></select>
        <button type="submit" class="tich-btn tich-btn-secondary">Filter</button>
    </form>

    <div class="tich-card tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead><tr><th>Code</th><th>Item</th><th>Category</th><th>Stock</th><th>Reorder</th><th>Location</th><th>Cost</th><th></th></tr></thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td><a href="{{ route('procurement.inventory-items.show', $item) }}" class="tich-link">{{ $item->item_code }}</a></td>
                            <td>{{ $item->item_name }}</td>
                            <td>{{ $item->category ?? '-' }}</td>
                            <td>{{ $item->current_stock }}</td>
                            <td>{{ $item->reorder_level }}</td>
                            <td>{{ $item->store_location ?? '-' }}</td>
                            <td>KES {{ number_format((float) $item->unit_cost, 2) }}</td>
                            <td><a href="{{ route('procurement.inventory-items.show', $item) }}" class="tich-link">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="tich-table-empty">No items.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="tich-mt-4">{{ $items->links() }}</div>
@endsection

@extends('layouts.procurement')

@section('title', 'Inventory')

@section('procurement-content')
    <x-page-toolbar title="Inventory items" meta="Stock ledger, reorder points">
        <x-slot:actions>
            <a href="{{ route('procurement.inventory-items.create') }}" class="tich-btn tich-btn-primary">+ Add item</a>
        </x-slot:actions>
    </x-page-toolbar>

    <form method="get" class="tich-filter-bar tich-mt-6 tich-mb-4">
        <input type="search" name="search" value="{{ $search ?? request('search') }}" class="tich-input" placeholder="Search item name or code…">
        <select name="category" class="tich-input"><option value="">All categories</option></select>
        @if(request()->filled('per_page'))
            <input type="hidden" name="per_page" value="{{ request('per_page') }}">
        @endif
        <button type="submit" class="tich-btn tich-btn-secondary">Filter</button>
    </form>

    <div class="tich-card tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th class="tich-col-num">#</th>
                        <th>Code</th>
                        <th>Item</th>
                        <th>Category</th>
                        <th>Stock</th>
                        <th>Reorder</th>
                        <th>Location</th>
                        <th>Cost</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td class="tich-col-num">{{ $items->firstItem() + $loop->index }}</td>
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
                        <tr><td colspan="9" class="tich-table-empty">No items.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('partials.pagination-footer', ['paginator' => $items])
@endsection

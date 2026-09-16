@extends('layouts.procurement')

@section('title', 'Goods Received Notes')

@section('procurement-content')
    <x-page-toolbar title="GRNs" meta="Goods received against purchase orders">
        <x-slot:actions>
            <a href="{{ route('procurement.grns.create') }}" class="tich-btn tich-btn-primary">+ New GRN</a>
        </x-slot:actions>
    </x-page-toolbar>

    <form method="get" class="tich-filter-bar tich-mt-6 tich-mb-4">
        <input type="search" name="search" value="{{ $search ?? '' }}" class="tich-input" placeholder="Search GRN or PO number…">
        <select name="status" class="tich-input"><option value="">All statuses</option><option value="pending" @selected(($status ?? request('status')) === 'pending')>Pending</option><option value="complete" @selected(($status ?? request('status')) === 'complete')>Complete</option></select>
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
                        <th>GRN #</th>
                        <th>PO</th>
                        <th>Supplier</th>
                        <th>Received by</th>
                        <th>Date</th>
                        <th>Inspection</th>
                        <th>Items</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($grns as $grn)
                        <tr>
                            <td class="tich-col-num">{{ $grns->firstItem() + $loop->index }}</td>
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
                        <tr><td colspan="9" class="tich-table-empty">No GRNs found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('partials.pagination-footer', ['paginator' => $grns])
@endsection

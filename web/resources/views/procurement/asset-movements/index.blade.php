@extends('layouts.procurement')

@section('title', 'Asset movements')

@section('procurement-content')
    <x-page-toolbar title="Asset movements" meta="Custodian and location transfers">
        <x-slot:actions>
            <a href="{{ route('procurement.asset-movements.create') }}" class="tich-btn tich-btn-primary">+ New request</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th class="tich-col-num">#</th>
                        <th>Asset</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Date</th>
                        <th>Requester</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $movement)
                        <tr>
                            <td class="tich-col-num">{{ $movements->firstItem() + $loop->index }}</td>
                            <td><a href="{{ route('procurement.assets.show', $movement->asset) }}" class="tich-link">{{ $movement->asset->asset_name }}</a></td>
                            <td>{{ $movement->from_location ?? '-' }}</td>
                            <td>{{ $movement->to_location ?? '-' }}</td>
                            <td>{{ $movement->movement_date?->format('d M Y') ?? '-' }}</td>
                            <td>{{ $movement->requestedBy?->full_name ?? '-' }}</td>
                            <td>{{ ucfirst($movement->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="tich-table-empty">No requests.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('partials.pagination-footer', ['paginator' => $movements])
@endsection

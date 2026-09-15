@extends('layouts.procurement')

@section('title', 'Stock alerts')

@section('procurement-content')
    <x-page-toolbar title="Low stock alerts" meta="Automated reorder and monitoring">
        <x-slot:actions>
            <a href="{{ route('procurement.inventory-items.index') }}" class="tich-btn tich-btn-secondary">Manage items</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--4 tich-mt-6">
        <article class="tich-card tich-stat"><p class="tich-caption">Active alerts</p><p class="tich-stat__value">{{ $stats['active'] }}</p></article>
        <article class="tich-card tich-stat"><p class="tich-caption">Closed</p><p class="tich-stat__value">{{ $stats['closed'] }}</p></article>
        <article class="tich-card tich-stat"><p class="tich-caption">Triggered today</p><p class="tich-stat__value">{{ $stats['triggered_today'] }}</p></article>
        <article class="tich-card tich-stat"><p class="tich-caption">Open requisitions</p><p class="tich-stat__value">{{ \App\Models\ProcurementRequisition::where('status', 'draft')->count() }}</p></article>
    </div>

    <div class="tich-card tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead><tr><th>Item</th><th>Code</th><th>Current</th><th>Reorder</th><th>Recommended</th><th>Triggered</th><th>Requisition</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse($alerts as $alert)
                        <tr>
                            <td><a href="{{ route('procurement.inventory-items.show', $alert->inventoryItem) }}" class="tich-link">{{ $alert->inventoryItem?->item_name ?? '-' }}</a></td>
                            <td>{{ $alert->inventoryItem?->item_code ?? '-' }}</td>
                            <td>{{ $alert->current_stock }}</td>
                            <td>{{ $alert->reorder_level }}</td>
                            <td>{{ $alert->recommended_quantity }}</td>
                            <td>{{ $alert->triggered_at?->format('d M Y') ?? '-' }}</td>
                            <td>{{ $alert->requisition_id ? '#' . $alert->requisition_id : '-' }}</td>
                            <td>
                                <form method="POST" action="{{ route('procurement.stock-alerts.auto-reorder', $alert) }}" class="tich-inline-form">
                                    @csrf
                                    <button type="submit" class="tich-btn tich-btn-primary">Auto-reorder</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="tich-table-empty">No active alerts.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="tich-mt-4">{{ $alerts->links() }}</div>
@endsection

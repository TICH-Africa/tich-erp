@extends('layouts.procurement')

@section('title', 'Stock alert')

@section('procurement-content')
    <x-page-toolbar title="Low stock alert" meta="{{ $alert->inventoryItem?->item_code ?? '-' }}">
        <x-slot:actions>
            <a href="{{ route('procurement.stock-alerts.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--4 tich-mt-6">
        <article class="tich-card tich-stat"><p class="tich-caption">Current stock</p><p class="tich-stat__value">{{ $alert->current_stock }}</p></article>
        <article class="tich-card tich-stat"><p class="tich-caption">Reorder level</p><p class="tich-stat__value">{{ $alert->reorder_level }}</p></article>
        <article class="tich-card tich-stat"><p class="tich-caption">Recommended</p><p class="tich-stat__value">{{ $alert->recommended_quantity }}</p></article>
        <article class="tich-card tich-stat"><p class="tich-caption">Status</p><p class="tich-stat__value">{{ ucfirst($alert->status) }}</p></article>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-6" style="gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3" style="margin-top:0;">Item</h2>
            <dl class="tich-dl">
                <dt>Name</dt><dd>{{ $alert->inventoryItem?->item_name ?? '-' }}</dd>
                <dt>Category</dt><dd>{{ $alert->inventoryItem?->category ?? '-' }}</dd>
                <dt>Unit</dt><dd>{{ $alert->inventoryItem?->unit_of_measure ?? '-' }}</dd>
                <dt>Supplier</dt><dd>{{ $alert->inventoryItem?->supplier?->supplier_name ?? '-' }}</dd>
                <dt>Location</dt><dd>{{ $alert->inventoryItem?->store_location ?? '-' }}</dd>
            </dl>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3" style="margin-top:0;">Alert</h2>
            <dl class="tich-dl">
                <dt>Triggered</dt><dd>{{ $alert->triggered_at?->format('d M Y H:i') ?? '-' }}</dd>
                <dt>Channels</dt><dd>{{ $alert->channels ? implode(', ', $alert->channels) : '-' }}</dd>
                <dt>Requisition</dt>
                <dd>
                    @if($alert->requisition)
                        <a href="{{ route('procurement.requisitions.show', $alert->requisition_id) }}" class="tich-link">{{ $alert->requisition->requisition_number }}</a>
                    @else
                        -
                    @endif
                </dd>
                <dt>Notes</dt><dd>{{ $alert->notes ?? '-' }}</dd>
            </dl>
        </article>
    </div>

    @if($alert->status === 'active')
        <form method="POST" action="{{ route('procurement.stock-alerts.auto-reorder', $alert) }}" class="tich-mt-6">
            @csrf
            <button type="submit" class="tich-btn tich-btn-primary">Auto-draft requisition</button>
        </form>
    @endif
@endsection

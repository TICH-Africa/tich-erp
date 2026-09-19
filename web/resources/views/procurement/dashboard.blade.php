@extends('layouts.procurement')

@section('title', 'Procurement Dashboard')

@section('procurement-content')
@php
    $supplierCount = \App\Models\Supplier::count();
    $requisitionCount = \App\Models\ProcurementRequisition::count();
    $assetCount = \App\Models\Asset::count();
    $inventoryCount = \App\Models\InventoryItem::count();
@endphp

<div class="tich-mod-dash">
    <header class="tich-mod-dash__hero">
        <div class="tich-mod-dash__hero-copy">
            <p class="tich-mod-dash__eyebrow">Supply chain &amp; assets</p>
            <h1 class="tich-mod-dash__title">Procurement command center</h1>
            <p class="tich-mod-dash__lede">Suppliers, purchase orders, tenders, inventory, and fixed assets — live overview.</p>
        </div>
    </header>

    @include('qa.partials.assigned-tasks-panel')

    <section class="tich-mod-dash__metrics" aria-label="Key procurement metrics">
        <article class="tich-mod-dash__metric">
            <p class="tich-mod-dash__metric-label">Suppliers</p>
            <p class="tich-mod-dash__metric-value">{{ $supplierCount }}</p>
        </article>
        <article class="tich-mod-dash__metric tich-mod-dash__metric--info">
            <p class="tich-mod-dash__metric-label">Requisitions</p>
            <p class="tich-mod-dash__metric-value">{{ $requisitionCount }}</p>
        </article>
        <article class="tich-mod-dash__metric tich-mod-dash__metric--ok">
            <p class="tich-mod-dash__metric-label">Assets</p>
            <p class="tich-mod-dash__metric-value">{{ $assetCount }}</p>
        </article>
        <article class="tich-mod-dash__metric">
            <p class="tich-mod-dash__metric-label">Inventory items</p>
            <p class="tich-mod-dash__metric-value">{{ $inventoryCount }}</p>
        </article>
    </section>

    <section class="tich-mod-dash__nav" aria-label="Procurement shortcuts">
        <p class="tich-mod-dash__section-label">Quick routes</p>
        <div class="tich-mod-dash__nav-grid">
            <a href="{{ route('procurement.assets.create') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">01</span>
                <h3 class="tich-mod-dash__nav-title">Register asset</h3>
                <p class="tich-mod-dash__nav-text">Add a fixed asset to the registry with location and custodian.</p>
            </a>
            <a href="{{ route('procurement.grns.create') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">02</span>
                <h3 class="tich-mod-dash__nav-title">Create GRN</h3>
                <p class="tich-mod-dash__nav-text">Receive goods against a purchase order.</p>
            </a>
            <a href="{{ route('procurement.inventory-items.create') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">03</span>
                <h3 class="tich-mod-dash__nav-title">Add inventory item</h3>
                <p class="tich-mod-dash__nav-text">Register a new stock item for stores.</p>
            </a>
            <a href="{{ route('procurement.stock-alerts.index') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">04</span>
                <h3 class="tich-mod-dash__nav-title">Stock alerts</h3>
                <p class="tich-mod-dash__nav-text">Review low-stock and reorder notifications.</p>
            </a>
            <a href="{{ route('procurement.suppliers.index') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">05</span>
                <h3 class="tich-mod-dash__nav-title">Suppliers</h3>
                <p class="tich-mod-dash__nav-text">Manage supplier profiles and compliance.</p>
            </a>
            <a href="{{ route('procurement.assets.index') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">06</span>
                <h3 class="tich-mod-dash__nav-title">Asset registry</h3>
                <p class="tich-mod-dash__nav-text">Browse assets and transfer from the asset page.</p>
            </a>
        </div>
    </section>
</div>
@endsection

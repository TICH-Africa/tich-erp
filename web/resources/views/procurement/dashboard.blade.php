@extends('layouts.procurement')

@section('title', 'Procurement Dashboard')

@section('procurement-content')
    <x-page-toolbar title="Procurement & Logistics" meta="Suppliers, purchase orders, tenders, inventory, assets" />

    @include('qa.partials.assigned-tasks-panel')

    <div class="tich-grid tich-grid--4 tich-mt-6">
        <article class="tich-card tich-stat">
            <p class="tich-caption">Suppliers</p>
            <p class="tich-stat__value">{{ \App\Models\Supplier::count() }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Requisitions</p>
            <p class="tich-stat__value">{{ \App\Models\ProcurementRequisition::count() }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Assets</p>
            <p class="tich-stat__value">{{ \App\Models\Asset::count() }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Inventory items</p>
            <p class="tich-stat__value">{{ \App\Models\InventoryItem::count() }}</p>
        </article>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-6">
        <a href="{{ route('procurement.assets.create') }}" class="tich-card tich-card--link">Register asset</a>
        <a href="{{ route('procurement.grns.create') }}" class="tich-card tich-card--link">Create GRN</a>
        <a href="{{ route('procurement.inventory-items.create') }}" class="tich-card tich-card--link">Add inventory item</a>
        <a href="{{ route('procurement.stock-alerts.index') }}" class="tich-card tich-card--link">View stock alerts</a>
    </div>

    <article class="tich-card tich-mt-6">
        <p class="tich-text">Use the sidebar to manage suppliers, RFQs, purchase orders, assets, inventory, GRNs, and stores issues.</p>
    </article>
@endsection

@extends('layouts.procurement')

@section('title', 'Asset ' . $asset->asset_number)

@section('procurement-content')
    <x-page-toolbar title="{{ $asset->asset_name }}" meta="{{ $asset->asset_number }}">
        <x-slot:actions>
            <a href="{{ route('procurement.assets.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--4 tich-mt-6">
        <article class="tich-card tich-stat"><p class="tich-caption">Acquisition cost</p><p class="tich-stat__value">KES {{ number_format((float) $asset->acquisition_cost, 2) }}</p></article>
        <article class="tich-card tich-stat"><p class="tich-caption">Current value</p><p class="tich-stat__value">KES {{ number_format((float) $asset->current_value, 2) }}</p></article>
        <article class="tich-card tich-stat"><p class="tich-caption">Custodian</p><p class="tich-stat__value">{{ $asset->custodian?->first_name ?? '' }} {{ $asset->custodian?->surname ?? '' }}</p></article>
        <article class="tich-card tich-stat"><p class="tich-caption">Status</p><p class="tich-stat__value">{{ ucfirst($asset->asset_status) }}</p></article>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-6" style="gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3" style="margin-top:0;">Details</h2>
            <dl class="tich-dl">
                <dt>Asset number</dt><dd>{{ $asset->asset_number }}</dd>
                <dt>Serial</dt><dd>{{ $asset->serial_number ?? '-' }}</dd>
                <dt>Tag</dt><dd>{{ $asset->tag_number ?? '-' }}</dd>
                <dt>Category</dt><dd>{{ $asset->asset_category }}</dd>
                <dt>Description</dt><dd>{{ $asset->description ?? '-' }}</dd>
                <dt>Acquisition date</dt><dd>{{ $asset->acquisition_date?->format('d M Y') ?? '-' }}</dd>
                <dt>Depreciation/yr</dt><dd>KES {{ number_format((float) $asset->depreciation_per_year, 2) }}</dd>
                <dt>Warranty</dt><dd>{{ $asset->warranty_expiry_date?->format('d M Y') ?? '-' }}</dd>
                <dt>Location</dt><dd>{{ $asset->location_name ?? '-' }} {{ $asset->building ? '/ ' . $asset->room : '' }}</dd>
                <dt>Supplier</dt><dd>{{ $asset->supplier?->supplier_name ?? '-' }}</dd>
                <dt>Purchase order</dt><dd>{{ $asset->purchaseOrder?->po_number ?? '-' }}</dd>
                @if($asset->procurementRequisition)
                <dt>Requisition</dt><dd><a href="{{ route('procurement.requisitions.show', $asset->procurementRequisition) }}" class="tich-link">{{ $asset->procurementRequisition->requisition_number }}</a></dd>
                @endif
            </dl>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3" style="margin-top:0;">Linked records</h2>
            <p class="tich-caption">Movements</p>
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead><tr><th>To</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($asset->movements as $m)
                            <tr><td>{{ $m->to_location ?? '-' }}</td><td>{{ $m->movement_date?->format('d M Y') ?? '-' }}</td><td>{{ ucfirst($m->status) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="tich-table-empty">None</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="tich-caption tich-mt-4">Maintenance</p>
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead><tr><th>Type</th><th>Priority</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($asset->maintenances as $m)
                            <tr><td>{{ $m->maintenance_type }}</td><td>{{ $m->priority }}</td><td>{{ $m->scheduled_date?->format('d M Y') ?? '-' }}</td><td>{{ ucfirst($m->status) }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="tich-table-empty">None</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="tich-caption tich-mt-4">Disposals</p>
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead><tr><th>Type</th><th>Date</th><th>Value</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($asset->disposals as $d)
                            <tr><td>{{ $d->disposal_type }}</td><td>{{ $d->disposal_date?->format('d M Y') ?? '-' }}</td><td>KES {{ number_format((float) $d->disposed_value, 2) }}</td><td>{{ ucfirst($d->status) }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="tich-table-empty">None</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="tich-caption tich-mt-4">Audits</p>
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead><tr><th>Status</th><th>Condition</th><th>Date</th></tr></thead>
                    <tbody>
                        @forelse($asset->audits as $a)
                            <tr><td>{{ $a->verification_status }}</td><td>{{ $a->condition }}</td><td>{{ $a->submitted_at?->format('d M Y') ?? '-' }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="tich-table-empty">None</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </div>
@endsection

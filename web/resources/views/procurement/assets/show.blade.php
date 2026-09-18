@extends('layouts.procurement')

@section('title', 'Asset ' . $asset->asset_number)

@php
    $openTransferModal = $errors->any();
@endphp

@section('procurement-content')
    <x-page-toolbar title="{{ $asset->asset_name }}" meta="{{ $asset->asset_number }}">
        <x-slot:actions>
            <button type="button" class="tich-btn tich-btn-primary" data-open-modal="asset-transfer-modal">Transfer asset</button>
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
                <dt>Location</dt><dd>{{ $asset->location_name ?? '-' }} {{ $asset->building ? '/ ' . $asset->building : '' }} {{ $asset->room ? '/ ' . $asset->room : '' }}</dd>
                <dt>Supplier</dt><dd>{{ $asset->supplier?->supplier_name ?? '-' }}</dd>
                <dt>Purchase order</dt><dd>{{ $asset->purchaseOrder?->po_number ?? '-' }}</dd>
                @if($asset->procurementRequisition)
                <dt>Requisition</dt><dd><a href="{{ route('procurement.requisitions.show', $asset->procurementRequisition) }}" class="tich-link">{{ $asset->procurementRequisition->requisition_number }}</a></dd>
                @endif
            </dl>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3" style="margin-top:0;">Transfer history</h2>
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead><tr><th>From</th><th>To</th><th>Type</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($asset->movements as $m)
                            <tr>
                                <td>{{ $m->from_location ?? '-' }}</td>
                                <td>{{ $m->to_location ?? '-' }}</td>
                                <td>{{ ucfirst($m->movement_type) }}</td>
                                <td>{{ $m->movement_date?->format('d M Y') ?? '-' }}</td>
                                <td>{{ ucfirst($m->status) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="tich-table-empty">No transfers yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-6" style="gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3" style="margin-top:0;">Linked records</h2>
            <p class="tich-caption">Disposals</p>
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

    <div
        id="asset-transfer-modal"
        class="tich-modal{{ $openTransferModal ? ' is-open' : '' }}"
        aria-hidden="{{ $openTransferModal ? 'false' : 'true' }}"
        role="dialog"
        aria-modal="true"
        aria-labelledby="asset-transfer-modal-title"
    >
        <div class="tich-modal__backdrop" data-close-modal="asset-transfer-modal"></div>
        <div class="tich-modal__dialog tich-modal__dialog--wide">
            <header class="tich-modal__header">
                <h2 class="tich-h3" id="asset-transfer-modal-title">Transfer asset</h2>
                <button type="button" class="tich-modal__close" data-close-modal="asset-transfer-modal" aria-label="Close">&times;</button>
            </header>
            <form method="POST" action="{{ route('procurement.assets.transfer', $asset) }}" class="tich-modal__body">
                @csrf
                <p class="tich-caption" style="margin-top:0;">Move this asset to a new location or custodian. The transfer is recorded against this asset.</p>

                @if ($errors->any())
                    <div class="tich-modal__errors">
                        <p class="tich-text" style="margin:0;">Please fix the highlighted fields and try again.</p>
                        <ul style="margin:0.5rem 0 0; padding-left:1.25rem;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="tich-grid tich-grid--2" style="gap:1rem;">
                    <div class="tich-form-group">
                        <label class="tich-label" for="movement_type">Type *</label>
                        <select id="movement_type" name="movement_type" class="tich-input" required>
                            <option value="transfer" @selected(old('movement_type', 'transfer') === 'transfer')>Transfer</option>
                            <option value="relocation" @selected(old('movement_type') === 'relocation')>Relocation</option>
                            <option value="assignment" @selected(old('movement_type') === 'assignment')>Assignment</option>
                        </select>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label" for="movement_date">Date *</label>
                        <input type="date" id="movement_date" name="movement_date" class="tich-input" value="{{ old('movement_date', now()->format('Y-m-d')) }}" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label" for="to_location">New location *</label>
                        <input type="text" id="to_location" name="to_location" class="tich-input" value="{{ old('to_location') }}" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label" for="custodian_id">Custodian</label>
                        <select id="custodian_id" name="custodian_id" class="tich-input">
                            <option value="">Keep current</option>
                            @foreach($custodians as $custodian)
                                <option value="{{ $custodian->id }}" @selected((string) old('custodian_id', $asset->custodian_id) === (string) $custodian->id)>
                                    {{ $custodian->first_name }} {{ $custodian->surname }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label" for="building">Building</label>
                        <input type="text" id="building" name="building" class="tich-input" value="{{ old('building', $asset->building) }}">
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label" for="room">Room</label>
                        <input type="text" id="room" name="room" class="tich-input" value="{{ old('room', $asset->room) }}">
                    </div>
                    <div class="tich-form-group" style="grid-column:1/-1;">
                        <label class="tich-label" for="reason">Reason *</label>
                        <textarea id="reason" name="reason" rows="3" class="tich-input" required>{{ old('reason') }}</textarea>
                    </div>
                    <div class="tich-form-group" style="grid-column:1/-1;">
                        <label class="tich-label" for="notes">Notes</label>
                        <textarea id="notes" name="notes" rows="2" class="tich-input">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="asset-transfer-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Transfer asset</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    @parent
    @include('admin.partials.tich-modal-assets')
@endsection

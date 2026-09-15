@extends('layouts.procurement')

@section('title', 'Register asset')

@section('procurement-content')
    <x-page-toolbar title="Register asset" meta="Add a fixed asset from a GRN or requisition">
        <x-slot:actions>
            <a href="{{ route('procurement.assets.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if ($errors->any())
        <div class="tich-alert tich-alert--error tich-mt-4">
            <ul style="margin:0; padding-left:1.25rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('procurement.assets.store') }}" class="tich-card tich-mt-6" enctype="multipart/form-data">
        @csrf

        <h2 class="tich-h3">Asset details</h2>
        <div class="tich-grid tich-grid--2" style="gap:1rem;">
            <div class="tich-form-group">
                <label class="tich-label" for="asset_name">Asset name *</label>
                <input type="text" id="asset_name" name="asset_name" class="tich-input" required>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="asset_category">Category *</label>
                <select id="asset_category" name="asset_category" class="tich-input" required>
                    <option value="">Select category</option>
                    @foreach(['furniture', 'ict_hardware', 'equipment', 'vehicle', 'building', 'other'] as $c)
                        <option value="{{ $c }}">{{ ucfirst(str_replace('_', ' ', $c)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="serial_number">Serial number</label>
                <input type="text" id="serial_number" name="serial_number" class="tich-input">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="tag_number">Tag / QR code</label>
                <input type="text" id="tag_number" name="tag_number" class="tich-input">
            </div>
            <div class="tich-form-group" style="grid-column:1/-1;">
                <label class="tich-label" for="description">Description</label>
                <textarea id="description" name="description" rows="2" class="tich-input"></textarea>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="acquisition_date">Acquisition date *</label>
                <input type="text" id="acquisition_date" name="acquisition_date" class="tich-input" placeholder="dd/mm/yyyy" required>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="acquisition_cost">Acquisition cost (KES) *</label>
                <input type="number" id="acquisition_cost" name="acquisition_cost" class="tich-input" step="0.01" min="0" required>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="useful_life_years">Useful life (years) *</label>
                <input type="number" id="useful_life_years" name="useful_life_years" class="tich-input" min="1" value="5" required>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="salvage_value">Salvage value</label>
                <input type="number" id="salvage_value" name="salvage_value" class="tich-input" step="0.01" min="0">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="warranty_expiry_date">Warranty expiry</label>
                <input type="date" id="warranty_expiry_date" name="warranty_expiry_date" class="tich-input">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="depreciation_method">Depreciation method</label>
                <select id="depreciation_method" name="depreciation_method" class="tich-input">
                    <option value="straight_line">Straight line</option>
                    <option value="declining_balance">Declining balance</option>
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="supplier_id">Supplier *</label>
                <select id="supplier_id" name="supplier_id" class="tich-input" required>
                    <option value="">Select supplier</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->supplier_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="purchase_order_id">Purchase order *</label>
                <select id="purchase_order_id" name="purchase_order_id" class="tich-input" required>
                    <option value="">Select PO</option>
                    @foreach($purchaseOrders as $po)
                        <option value="{{ $po->id }}">{{ $po->po_number }} — KES {{ number_format((float) $po->total_amount, 2) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="custodian_id">Custodian *</label>
                <select id="custodian_id" name="custodian_id" class="tich-input" required>
                    <option value="">Select staff</option>
                    @foreach($custodians as $staff)
                        <option value="{{ $staff->id }}">{{ $staff->first_name }} {{ $staff->surname }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="procurement_requisition_id">Procurement requisition</label>
                <select id="procurement_requisition_id" name="procurement_requisition_id" class="tich-input">
                    <option value="">None</option>
                    @foreach($requisitions as $req)
                        <option value="{{ $req->id }}">{{ $req->requisition_number }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <h2 class="tich-h3 tich-mt-6">Location</h2>
        <div class="tich-grid tich-grid--3" style="gap:1rem;">
            <div class="tich-form-group">
                <label class="tich-label" for="location_name">Location</label>
                <input type="text" id="location_name" name="location_name" class="tich-input">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="building">Building</label>
                <input type="text" id="building" name="building" class="tich-input">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="room">Room</label>
                <input type="text" id="room" name="room" class="tich-input">
            </div>
        </div>

        <div class="tich-mt-6">
            <button type="submit" class="tich-btn tich-btn-primary">Register asset</button>
        </div>
    </form>
@endsection

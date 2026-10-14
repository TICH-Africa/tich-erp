@extends('layouts.procurement')

@section('title', 'Edit inventory item')

@section('procurement-content')
    <x-page-toolbar title="Edit inventory item" meta="{{ $item->item_code }}">
        <x-slot:actions>
            <a href="{{ route('procurement.inventory-items.show', $item) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if ($errors->any())
        <div class="tich-alert tich-alert--error tich-mt-4">
            <ul style="margin:0; padding-left: 1.25rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="uf-form">
        <form method="POST" action="{{ route('procurement.inventory-items.update', $item) }}" data-uf="ready">
            @csrf
            @method('PUT')

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">INV · Inventory item</div>
                    <div class="uf-amount-bar__sum">Edit {{ $item->item_name }}</div>
                </div>
                <span class="uf-badge">Edit</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Item Identity</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="item_code">Item code <span class="uf-req">*</span></label>
                            <input type="text" id="item_code" name="item_code" required value="{{ old('item_code', $item->item_code) }}">
                        </div>
                        <div class="uf-field">
                            <label for="item_name">Item name <span class="uf-req">*</span></label>
                            <input type="text" id="item_name" name="item_name" required value="{{ old('item_name', $item->item_name) }}">
                        </div>
                        <div class="uf-field">
                            <label for="category">Category</label>
                            <input type="text" id="category" name="category" value="{{ old('category', $item->category) }}">
                        </div>
                        <div class="uf-field">
                            <label for="unit_of_measure">Unit of measure <span class="uf-req">*</span></label>
                            <input type="text" id="unit_of_measure" name="unit_of_measure" value="{{ old('unit_of_measure', $item->unit_of_measure) }}" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Stock Levels &amp; Cost</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="current_stock">Current stock <span class="uf-req">*</span></label>
                            <input type="number" id="current_stock" name="current_stock" min="0" required value="{{ old('current_stock', $item->current_stock) }}">
                        </div>
                        <div class="uf-field">
                            <label for="reorder_level">Reorder level <span class="uf-req">*</span></label>
                            <input type="number" id="reorder_level" name="reorder_level" min="0" required value="{{ old('reorder_level', $item->reorder_level) }}">
                        </div>
                        <div class="uf-field">
                            <label for="minimum_stock">Minimum stock</label>
                            <input type="number" id="minimum_stock" name="minimum_stock" min="0" value="{{ old('minimum_stock', $item->minimum_stock) }}">
                        </div>
                        <div class="uf-field">
                            <label for="maximum_stock">Maximum stock</label>
                            <input type="number" id="maximum_stock" name="maximum_stock" min="0" value="{{ old('maximum_stock', $item->maximum_stock) }}">
                        </div>
                        <div class="uf-field">
                            <label for="unit_cost">Unit cost <span class="uf-req">*</span></label>
                            <input type="number" id="unit_cost" name="unit_cost" step="0.01" min="0" required value="{{ old('unit_cost', $item->unit_cost) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Supplier &amp; Location</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="supplier_id">Supplier</label>
                            <select id="supplier_id" name="supplier_id">
                                <option value="">Select supplier</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" {{ old('supplier_id', $item->supplier_id) == $supplier->id ? 'selected' : '' }}>{{ $supplier->supplier_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="uf-field">
                            <label for="store_location">Store location</label>
                            <input type="text" id="store_location" name="store_location" value="{{ old('store_location', $item->store_location) }}">
                        </div>
                    </div>
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Save item</button>
                        <a href="{{ route('procurement.inventory-items.show', $item) }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
@endsection

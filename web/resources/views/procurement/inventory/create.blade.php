@extends('layouts.procurement')

@section('title', 'Add inventory item')

@section('procurement-content')
    <x-page-toolbar title="Add inventory item" meta="Consumable or stock item">
        <x-slot:actions>
            <a href="{{ route('procurement.inventory-items.index') }}" class="tich-btn tich-btn-ghost">Back</a>
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

    <div class="uf-form">
        <form method="POST" action="{{ route('procurement.inventory-items.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">INV · Inventory item</div>
                    <div class="uf-amount-bar__sum">Consumable or stock item</div>
                </div>
                <span class="uf-badge">New</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Item Identity</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="item_code">Item code <span class="uf-req">*</span></label>
                            <input type="text" id="item_code" name="item_code" required>
                        </div>
                        <div class="uf-field">
                            <label for="item_name">Item name <span class="uf-req">*</span></label>
                            <input type="text" id="item_name" name="item_name" required>
                        </div>
                        <div class="uf-field">
                            <label for="category">Category</label>
                            <input type="text" id="category" name="category">
                        </div>
                        <div class="uf-field">
                            <label for="unit_of_measure">Unit of measure <span class="uf-req">*</span></label>
                            <input type="text" id="unit_of_measure" name="unit_of_measure" value="unit" required>
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
                            <input type="number" id="current_stock" name="current_stock" min="0" required>
                        </div>
                        <div class="uf-field">
                            <label for="reorder_level">Reorder level <span class="uf-req">*</span></label>
                            <input type="number" id="reorder_level" name="reorder_level" min="0" required>
                        </div>
                        <div class="uf-field">
                            <label for="minimum_stock">Minimum stock</label>
                            <input type="number" id="minimum_stock" name="minimum_stock" min="0">
                        </div>
                        <div class="uf-field">
                            <label for="maximum_stock">Maximum stock</label>
                            <input type="number" id="maximum_stock" name="maximum_stock" min="0">
                        </div>
                        <div class="uf-field">
                            <label for="unit_cost">Unit cost <span class="uf-req">*</span></label>
                            <input type="number" id="unit_cost" name="unit_cost" step="0.01" min="0" required>
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
                                    <option value="{{ $supplier->id }}">{{ $supplier->supplier_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="uf-field">
                            <label for="store_location">Store location</label>
                            <input type="text" id="store_location" name="store_location">
                        </div>
                    </div>
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Add item</button>
                        <a href="{{ route('procurement.inventory-items.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
@endsection

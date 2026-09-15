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

    <form method="POST" action="{{ route('procurement.inventory-items.store') }}" class="tich-card tich-mt-6">
        @csrf
        <div class="tich-grid tich-grid--2" style="gap:1rem;">
            <div class="tich-form-group">
                <label class="tich-label" for="item_code">Item code *</label>
                <input type="text" id="item_code" name="item_code" class="tich-input" required>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="item_name">Item name *</label>
                <input type="text" id="item_name" name="item_name" class="tich-input" required>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="category">Category</label>
                <input type="text" id="category" name="category" class="tich-input">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="unit_of_measure">Unit of measure *</label>
                <input type="text" id="unit_of_measure" name="unit_of_measure" class="tich-input" value="unit" required>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="current_stock">Current stock *</label>
                <input type="number" id="current_stock" name="current_stock" class="tich-input" min="0" required>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="reorder_level">Reorder level *</label>
                <input type="number" id="reorder_level" name="reorder_level" class="tich-input" min="0" required>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="minimum_stock">Minimum stock</label>
                <input type="number" id="minimum_stock" name="minimum_stock" class="tich-input" min="0">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="maximum_stock">Maximum stock</label>
                <input type="number" id="maximum_stock" name="maximum_stock" class="tich-input" min="0">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="unit_cost">Unit cost *</label>
                <input type="number" id="unit_cost" name="unit_cost" class="tich-input" step="0.01" min="0" required>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="supplier_id">Supplier</label>
                <select id="supplier_id" name="supplier_id" class="tich-input">
                    <option value="">Select supplier</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->supplier_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="store_location">Store location</label>
                <input type="text" id="store_location" name="store_location" class="tich-input">
            </div>
        </div>
        <div class="tich-mt-6">
            <button type="submit" class="tich-btn tich-btn-primary">Add item</button>
        </div>
    </form>
@endsection

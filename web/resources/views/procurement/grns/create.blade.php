@extends('layouts.procurement')

@section('title', 'New GRN')

@section('procurement-content')
    <x-page-toolbar title="New GRN" meta="Receive goods against a purchase order">
        <x-slot:actions>
            <a href="{{ route('procurement.grns.index') }}" class="tich-btn tich-btn-ghost">Back</a>
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

    <form method="POST" action="{{ route('procurement.grns.store') }}" class="tich-card tich-mt-6">
        @csrf

        <h2 class="tich-h3">Receipt</h2>
        <div class="tich-grid tich-grid--3" style="gap:1rem;">
            <div class="tich-form-group">
                <label class="tich-label" for="purchase_order_id">Purchase order *</label>
                <select id="purchase_order_id" name="purchase_order_id" class="tich-input" required>
                    <option value="">Select PO</option>
                    @foreach($purchaseOrders as $po)
                        <option value="{{ $po->id }}">{{ $po->po_number }} — {{ $po->supplier->supplier_name }}</option>
                    @endforeach
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
                <label class="tich-label" for="received_date">Received date *</label>
                <input type="date" id="received_date" name="received_date" class="tich-input" required>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="supplier_delivery_note">Supplier delivery note</label>
                <input type="text" id="supplier_delivery_note" name="supplier_delivery_note" class="tich-input">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="received_by">Received by *</label>
                <select id="received_by" name="received_by" class="tich-input" required>
                    <option value="">Select staff</option>
                    @foreach($staff as $s)
                        <option value="{{ $s->id }}">{{ $s->first_name }} {{ $s->surname }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <h2 class="tich-h3 tich-mt-6">Items received</h2>
        <div class="tich-card tich-mt-4" id="grn-items">
            <div class="tich-grid tich-grid--2" style="gap:1rem;" data-item-index="0">
                <div class="tich-form-group">
                    <label class="tich-label">Item name *</label>
                    <input type="text" name="items[0][item_name]" class="tich-input" required>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Category</label>
                    <input type="text" name="items[0][category]" class="tich-input">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Classification *</label>
                    <select name="items[0][classification]" class="tich-input" required>
                        <option value="consumable">Consumable</option>
                        <option value="asset">Fixed asset</option>
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Quantity ordered *</label>
                    <input type="number" step="0.0001" name="items[0][quantity_ordered]" class="tich-input" required>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Quantity received *</label>
                    <input type="number" step="0.0001" name="items[0][quantity_received]" class="tich-input" required>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Unit cost *</label>
                    <input type="number" step="0.01" name="items[0][unit_cost]" class="tich-input" required>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Condition *</label>
                    <select name="items[0][condition]" class="tich-input" required>
                        <option value="good">Good</option>
                        <option value="partial">Partial</option>
                        <option value="damaged">Damaged</option>
                    </select>
                </div>
            </div>
        </div>
        <button type="button" onclick="addGrnItem()" class="tich-btn tich-btn-secondary tich-mt-2">Add item</button>

        <div class="tich-mt-6">
            <button type="submit" class="tich-btn tich-btn-primary">Create GRN</button>
        </div>
    </form>

    <script>
        function addGrnItem() {
            const container = document.getElementById('grn-items');
            const index = container.children.length;
            const div = document.createElement('div');
            div.className = 'tich-grid tich-grid--2';
            div.style.gap = '1rem';
            div.setAttribute('data-item-index', index);
            div.innerHTML = container.children[0].innerHTML.replace(/\[0\]/g, '[' + index + ']');
            container.appendChild(div);
        }
    </script>
@endsection

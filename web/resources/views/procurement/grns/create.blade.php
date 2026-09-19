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

    <div class="uf-form">
        <form method="POST" action="{{ route('procurement.grns.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">GRN · Goods receipt</div>
                    <div class="uf-amount-bar__sum">Receive against purchase order</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Receipt</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="purchase_order_id">Purchase order <span class="uf-req">*</span></label>
                            <select id="purchase_order_id" name="purchase_order_id" required>
                                <option value="">Select PO</option>
                                @foreach($purchaseOrders as $po)
                                    <option value="{{ $po->id }}">{{ $po->po_number }} — {{ $po->supplier->supplier_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="uf-field">
                            <label for="supplier_id">Supplier <span class="uf-req">*</span></label>
                            <select id="supplier_id" name="supplier_id" required>
                                <option value="">Select supplier</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->supplier_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="uf-field">
                            <label for="received_date">Received date <span class="uf-req">*</span></label>
                            <input type="date" id="received_date" name="received_date" required>
                        </div>
                        <div class="uf-field">
                            <label for="supplier_delivery_note">Supplier delivery note</label>
                            <input type="text" id="supplier_delivery_note" name="supplier_delivery_note">
                        </div>
                        <div class="uf-field">
                            <label for="received_by">Received by <span class="uf-req">*</span></label>
                            <select id="received_by" name="received_by" required>
                                <option value="">Select staff</option>
                                @foreach($staff as $s)
                                    <option value="{{ $s->id }}">{{ $s->first_name }} {{ $s->surname }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Items Received</div>
                <div class="uf-section-body">
                    <div id="grn-items">
                        <div class="uf-form-grid-2" data-item-index="0">
                            <div class="uf-field">
                                <label>Item name <span class="uf-req">*</span></label>
                                <input type="text" name="items[0][item_name]" required>
                            </div>
                            <div class="uf-field">
                                <label>Category</label>
                                <input type="text" name="items[0][category]">
                            </div>
                            <div class="uf-field">
                                <label>Classification <span class="uf-req">*</span></label>
                                <select name="items[0][classification]" required>
                                    <option value="consumable">Consumable</option>
                                    <option value="asset">Fixed asset</option>
                                </select>
                            </div>
                            <div class="uf-field">
                                <label>Quantity ordered <span class="uf-req">*</span></label>
                                <input type="number" step="0.0001" name="items[0][quantity_ordered]" required>
                            </div>
                            <div class="uf-field">
                                <label>Quantity received <span class="uf-req">*</span></label>
                                <input type="number" step="0.0001" name="items[0][quantity_received]" required>
                            </div>
                            <div class="uf-field">
                                <label>Unit cost <span class="uf-req">*</span></label>
                                <input type="number" step="0.01" name="items[0][unit_cost]" required>
                            </div>
                            <div class="uf-field">
                                <label>Condition <span class="uf-req">*</span></label>
                                <select name="items[0][condition]" required>
                                    <option value="good">Good</option>
                                    <option value="partial">Partial</option>
                                    <option value="damaged">Damaged</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="addGrnItem()" class="uf-btn uf-btn-secondary">Add item</button>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Create GRN</button>
                        <a href="{{ route('procurement.grns.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>

    <script>
        function addGrnItem() {
            const container = document.getElementById('grn-items');
            const index = container.children.length;
            const div = document.createElement('div');
            div.className = 'uf-form-grid-2';
            div.setAttribute('data-item-index', index);
            div.innerHTML = container.children[0].innerHTML.replace(/\[0\]/g, '[' + index + ']');
            container.appendChild(div);
        }
    </script>
@endsection

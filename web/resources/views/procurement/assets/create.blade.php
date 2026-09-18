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

    <div class="uf-form">
        <form method="POST" action="{{ route('procurement.assets.store') }}" enctype="multipart/form-data" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">AST · Fixed asset</div>
                    <div class="uf-amount-bar__sum">Register from GRN or requisition</div>
                </div>
                <span class="uf-badge">New</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Asset Details</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="asset_name">Asset name <span class="uf-req">*</span></label>
                            <input type="text" id="asset_name" name="asset_name" required>
                        </div>
                        <div class="uf-field">
                            <label for="asset_category">Category <span class="uf-req">*</span></label>
                            @php
                                $presetCategories = ['furniture', 'ict_hardware', 'equipment', 'vehicle', 'building'];
                                $oldCategory = old('asset_category', '');
                                $isOtherCategory = $oldCategory !== '' && ! in_array($oldCategory, $presetCategories, true);
                                $selectedCategory = $isOtherCategory ? 'other' : $oldCategory;
                            @endphp
                            <select id="asset_category" name="asset_category" required>
                                <option value="">Select category</option>
                                @foreach($presetCategories as $c)
                                    <option value="{{ $c }}" @selected($selectedCategory === $c)>{{ ucfirst(str_replace('_', ' ', $c)) }}</option>
                                @endforeach
                                <option value="other" @selected($selectedCategory === 'other')>Other</option>
                            </select>
                        </div>
                        <div class="uf-field" id="asset_category_other_wrap" @style(['display:none' => ! $isOtherCategory])>
                            <label for="asset_category_other">Specify category <span class="uf-req">*</span></label>
                            <input
                                type="text"
                                id="asset_category_other"
                                name="asset_category_other"
                                value="{{ old('asset_category_other', $isOtherCategory ? $oldCategory : '') }}"
                                placeholder="Type the category"
                                maxlength="50"
                                @if($isOtherCategory) required @endif
                            >
                        </div>
                        <div class="uf-field">
                            <label for="serial_number">Serial number</label>
                            <input type="text" id="serial_number" name="serial_number">
                        </div>
                        <div class="uf-field">
                            <label for="tag_number">Tag / QR code</label>
                            <input type="text" id="tag_number" name="tag_number">
                        </div>
                    </div>
                    <div class="uf-field">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="2"></textarea>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Financial &amp; Sourcing</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="acquisition_date">Acquisition date <span class="uf-req">*</span></label>
                            <input type="text" id="acquisition_date" name="acquisition_date" placeholder="dd/mm/yyyy" required>
                        </div>
                        <div class="uf-field">
                            <label for="acquisition_cost">Acquisition cost (KES) <span class="uf-req">*</span></label>
                            <input type="number" id="acquisition_cost" name="acquisition_cost" step="0.01" min="0" required>
                        </div>
                        <div class="uf-field">
                            <label for="useful_life_years">Useful life (years) <span class="uf-req">*</span></label>
                            <input type="number" id="useful_life_years" name="useful_life_years" min="1" value="5" required>
                        </div>
                        <div class="uf-field">
                            <label for="salvage_value">Salvage value</label>
                            <input type="number" id="salvage_value" name="salvage_value" step="0.01" min="0">
                        </div>
                        <div class="uf-field">
                            <label for="warranty_expiry_date">Warranty expiry</label>
                            <input type="date" id="warranty_expiry_date" name="warranty_expiry_date">
                        </div>
                        <div class="uf-field">
                            <label for="depreciation_method">Depreciation method</label>
                            <select id="depreciation_method" name="depreciation_method">
                                <option value="straight_line">Straight line</option>
                                <option value="declining_balance">Declining balance</option>
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
                            <label for="purchase_order_id">Purchase order <span class="uf-req">*</span></label>
                            <select id="purchase_order_id" name="purchase_order_id" required>
                                <option value="">Select PO</option>
                                @foreach($purchaseOrders as $po)
                                    <option value="{{ $po->id }}">{{ $po->po_number }} — KES {{ number_format((float) $po->total_amount, 2) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="uf-field">
                            <label for="custodian_id">Custodian <span class="uf-req">*</span></label>
                            <select id="custodian_id" name="custodian_id" required>
                                <option value="">Select staff</option>
                                @foreach($custodians as $staff)
                                    <option value="{{ $staff->id }}">{{ $staff->first_name }} {{ $staff->surname }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="uf-field">
                            <label for="procurement_requisition_id">Procurement requisition</label>
                            <select id="procurement_requisition_id" name="procurement_requisition_id">
                                <option value="">None</option>
                                @foreach($requisitions as $req)
                                    <option value="{{ $req->id }}">{{ $req->requisition_number }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Location &amp; Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="location_name">Location</label>
                            <input type="text" id="location_name" name="location_name">
                        </div>
                        <div class="uf-field">
                            <label for="building">Building</label>
                            <input type="text" id="building" name="building">
                        </div>
                        <div class="uf-field">
                            <label for="room">Room</label>
                            <input type="text" id="room" name="room">
                        </div>
                    </div>
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Register asset</button>
                        <a href="{{ route('procurement.assets.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>

    <script>
        (function () {
            var select = document.getElementById('asset_category');
            var wrap = document.getElementById('asset_category_other_wrap');
            var other = document.getElementById('asset_category_other');
            if (!select || !wrap || !other) return;

            function syncOtherCategory() {
                var isOther = select.value === 'other';
                wrap.style.display = isOther ? '' : 'none';
                other.required = isOther;
                if (!isOther) {
                    other.value = '';
                }
            }

            select.addEventListener('change', syncOtherCategory);
            syncOtherCategory();
        })();
    </script>
@endsection

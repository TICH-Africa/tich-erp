@extends('layouts.finance')

@section('title', 'Accounts Payable')

@section('finance-content')
    <x-page-toolbar title="Accounts Payable" meta="Create supplier invoice">
        <x-slot:actions>
            <a href="{{ route('finance.ap.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="uf-form">
        <form method="POST" action="{{ route('finance.ap.store') }}" data-uf="ready" id="ap-create-form">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">AP · Accounts payable</div>
                    <div class="uf-amount-bar__sum">Supplier invoice</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Supplier &amp; Invoice</div>
                <div class="uf-section-body">
                    <div class="uf-field">
                        <label for="supplier-search">Supplier <span class="uf-req">*</span></label>
                        <input
                            type="text"
                            id="supplier-search"
                            placeholder="Search supplier..."
                            autocomplete="off"
                            required
                            class="{{ $errors->has('supplier_id') ? 'is-invalid' : '' }}"
                        >
                        <select
                            name="supplier_id"
                            id="supplier-select"
                            required
                            class="{{ $errors->has('supplier_id') ? 'is-invalid' : '' }}"
                        >
                            <option value="">Select supplier</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->supplier_name }} ({{ $supplier->supplier_code }})</option>
                            @endforeach
                        </select>
                        @error('supplier_id')
                            <span class="uf-error">{{ $message }}</span>
                        @enderror
                        <span class="uf-hint">Search for a supplier to auto-fill the dropdown.</span>
                    </div>
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="invoice_number">Invoice number <span class="uf-req">*</span></label>
                            <input
                                type="text"
                                id="invoice_number"
                                name="invoice_number"
                                placeholder="e.g. INV-2026-001"
                                required
                                class="{{ $errors->has('invoice_number') ? 'is-invalid' : '' }}"
                            >
                            @error('invoice_number')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="due_date">Due date <span class="uf-req">*</span></label>
                            <input
                                type="date"
                                id="due_date"
                                name="due_date"
                                required
                                class="{{ $errors->has('due_date') ? 'is-invalid' : '' }}"
                            >
                            @error('due_date')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="invoice_amount">Invoice amount (KES) <span class="uf-req">*</span></label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                id="invoice_amount"
                                name="invoice_amount"
                                placeholder="0.00"
                                required
                                class="{{ $errors->has('invoice_amount') ? 'is-invalid' : '' }}"
                            >
                            @error('invoice_amount')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="tax_amount">Tax amount (KES)</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                id="tax_amount"
                                name="tax_amount"
                                placeholder="0.00"
                                value="0"
                                class="{{ $errors->has('tax_amount') ? 'is-invalid' : '' }}"
                            >
                            @error('tax_amount')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="uf-field">
                        <label for="description">Description</label>
                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            placeholder="Optional notes..."
                            class="{{ $errors->has('description') ? 'is-invalid' : '' }}"
                        ></textarea>
                        @error('description')
                            <span class="uf-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Create invoice</button>
                        <a href="{{ route('finance.ap.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('supplier-search');
            const select = document.getElementById('supplier-select');
            if (!searchInput || !select) return;

            searchInput.addEventListener('input', function () {
                const query = this.value.trim();
                if (query.length < 1) return;

                fetch(`{{ route('finance.api.suppliers') }}?search=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(suppliers => {
                        select.innerHTML = '<option value="">Select supplier</option>';
                        suppliers.forEach(supplier => {
                            const option = document.createElement('option');
                            option.value = supplier.id;
                            option.textContent = `${supplier.supplier_name} (${supplier.supplier_code})`;
                            select.appendChild(option);
                        });
                    })
                    .catch(() => {
                        select.innerHTML = '<option value="">Select supplier</option>';
                    });
            });

            select.addEventListener('change', function () {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption && selectedOption.value) {
                    searchInput.value = selectedOption.textContent.replace(/\s*\([^)]*\)/, '').trim();
                }
            });
        });
    </script>
@endsection

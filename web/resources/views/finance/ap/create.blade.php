@extends('layouts.finance')

@section('title', 'Accounts Payable')

@section('finance-content')
    <x-page-toolbar title="Accounts Payable" meta="Create supplier invoice">
        <x-slot:actions>
            <a href="{{ route('finance.ap.index', $department) }}" class="tich-btn tich-btn-ghost">Back</a>
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

    <form method="POST" action="{{ route('finance.ap.store', $department) }}" class="tich-card tich-form-grid" id="ap-create-form">
        @csrf
        <div class="tich-form-row">
            <label class="tich-label" for="supplier-search">Supplier <span class="tich-text--danger">*</span></label>
            <input type="text" id="supplier-search" class="tich-input" placeholder="Search supplier..." autocomplete="off" required>
            <select name="supplier_id" id="supplier-select" class="tich-input" required>
                <option value="">Select supplier</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->supplier_name }} ({{ $supplier->supplier_code }})</option>
                @endforeach
            </select>
            <p class="tich-caption tich-mt-2">Search for a supplier to auto-fill the dropdown.</p>
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="invoice_number">Invoice number <span class="tich-text--danger">*</span></label>
            <input type="text" id="invoice_number" name="invoice_number" class="tich-input" placeholder="e.g. INV-2026-001" required>
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="invoice_amount">Invoice amount (KES) <span class="tich-text--danger">*</span></label>
            <input type="number" step="0.01" min="0" id="invoice_amount" name="invoice_amount" class="tich-input" placeholder="0.00" required>
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="tax_amount">Tax amount (KES)</label>
            <input type="number" step="0.01" min="0" id="tax_amount" name="tax_amount" class="tich-input" placeholder="0.00" value="0">
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="due_date">Due date <span class="tich-text--danger">*</span></label>
            <input type="date" id="due_date" name="due_date" class="tich-input" required>
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="description">Description</label>
            <textarea id="description" name="description" class="tich-input" rows="4" placeholder="Optional notes..."></textarea>
        </div>
        <div class="tich-form-row">
            <button type="submit" class="tich-btn tich-btn-primary">Create invoice</button>
            <a href="{{ route('finance.ap.index', $department) }}" class="tich-btn tich-btn-ghost" style="margin-left: 0.5rem;">Cancel</a>
        </div>
    </form>

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

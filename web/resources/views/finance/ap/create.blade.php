@extends('layouts.finance')

@section('title', 'Accounts Payable')

@section('finance-content')
    <x-page-toolbar title="Accounts Payable" meta="Create supplier invoice">
        <x-slot:actions>
            <a href="{{ route('finance.ap.index', $department) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card">
        <form method="POST" action="{{ route('finance.ap.store', $department) }}" class="tich-form" id="ap-create-form">
            @csrf
            <div class="tich-form__row">
                <label class="tich-form__label">Supplier</label>
                <input type="text" id="supplier-search" class="tich-form__input" placeholder="Search supplier..." autocomplete="off" required>
                <select name="supplier_id" id="supplier-select" class="tich-form__input" required>
                    <option value="">Select supplier</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->supplier_name }} ({{ $supplier->supplier_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form__row">
                <label class="tich-form__label">Invoice number</label>
                <input type="text" name="invoice_number" class="tich-form__input" required />
            </div>
            <div class="tich-form__row">
                <label class="tich-form__label">Invoice amount (KES)</label>
                <input type="number" name="invoice_amount" class="tich-form__input" step="0.01" min="0" required />
            </div>
            <div class="tich-form__row">
                <label class="tich-form__label">Tax amount (KES, optional)</label>
                <input type="number" name="tax_amount" class="tich-form__input" step="0.01" min="0" value="0" />
            </div>
            <div class="tich-form__row">
                <label class="tich-form__label">Due date</label>
                <input type="date" name="due_date" class="tich-form__input" required />
            </div>
            <div class="tich-form__row">
                <label class="tich-form__label">Description</label>
                <textarea name="description" class="tich-form__input" rows="3"></textarea>
            </div>
            <button type="submit" class="tich-btn tich-btn-primary">Create invoice</button>
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

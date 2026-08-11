@extends('layouts.finance')

@section('title', 'Accounts Payable')

@section('department-content')
    <x-page-toolbar title="Accounts Payable" meta="Create supplier invoice">
        <x-slot:actions>
            <a href="{{ route('finance.ap.index', $department) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card">
        <form method="POST" action="{{ route('finance.ap.store', $department) }}" class="tich-form">
            @csrf
            <div class="tich-form__row">
                <label class="tich-form__label">Supplier</label>
                <select name="supplier_id" class="tich-form__input" required>
                    <option value="">Select supplier</option>
                </select>
            </div>
            <div class="tich-form__row">
                <label class="tich-form__label">Invoice number</label>
                <input type="text" name="invoice_number" class="tich-form__input" required />
            </div>
            <div class="tich-form__row">
                <label class="tich-form__label">Amount</label>
                <input type="number" name="amount" class="tich-form__input" step="0.01" required />
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
@endsection


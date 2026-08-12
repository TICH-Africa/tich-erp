@extends('layouts.finance')

@section('title', 'General Ledger')

@section('finance-content')
    <x-page-toolbar title="General Ledger" meta="Create journal entry">
        <x-slot:actions>
            <a href="{{ route('finance.gl.index', $department) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card">
        <form method="POST" action="{{ route('finance.gl.journal.store', $department) }}" class="tich-form">
            @csrf
            <div class="tich-form__row">
                <label class="tich-form__label">Date</label>
                <input type="date" name="date" class="tich-form__input" required />
            </div>
            <div class="tich-form__row">
                <label class="tich-form__label">Description</label>
                <textarea name="description" class="tich-form__input" rows="2" required></textarea>
            </div>
            <div class="tich-form__row">
                <label class="tich-form__label">Debit account</label>
                <select name="debit_account_id" class="tich-form__input" required>
                    <option value="">Select account</option>
                </select>
            </div>
            <div class="tich-form__row">
                <label class="tich-form__label">Credit account</label>
                <select name="credit_account_id" class="tich-form__input" required>
                    <option value="">Select account</option>
                </select>
            </div>
            <div class="tich-form__row">
                <label class="tich-form__label">Amount</label>
                <input type="number" name="amount" class="tich-form__input" step="0.01" required />
            </div>
            <button type="submit" class="tich-btn tich-btn-primary">Post entry</button>
        </form>
    </div>
@endsection


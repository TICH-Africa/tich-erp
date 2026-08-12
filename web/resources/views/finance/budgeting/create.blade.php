@extends('layouts.finance')

@section('title', 'Budgeting')

@section('finance-content')
    <x-page-toolbar title="Budgeting" meta="Create budget">
        <x-slot:actions>
            <a href="{{ route('finance.budgeting.index', $department) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card">
        <form method="POST" action="{{ route('finance.budgeting.store', $department) }}" class="tich-form">
            @csrf
            <div class="tich-form__row">
                <label class="tich-form__label">Name</label>
                <input type="text" name="name" class="tich-form__input" required />
            </div>
            <div class="tich-form__row">
                <label class="tich-form__label">Period</label>
                <input type="text" name="period" class="tich-form__input" placeholder="e.g. 2026" required />
            </div>
            <div class="tich-form__row">
                <label class="tich-form__label">Department</label>
                <select name="department_id" class="tich-form__input" required>
                    <option value="">Select department</option>
                </select>
            </div>
            <div class="tich-form__row">
                <label class="tich-form__label">Amount</label>
                <input type="number" name="amount" class="tich-form__input" step="0.01" required />
            </div>
            <div class="tich-form__row">
                <label class="tich-form__label">Notes</label>
                <textarea name="notes" class="tich-form__input" rows="3"></textarea>
            </div>
            <button type="submit" class="tich-btn tich-btn-primary">Create budget</button>
        </form>
    </div>
@endsection


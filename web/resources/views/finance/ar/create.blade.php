@extends('layouts.finance')

@section('title', 'Accounts Receivable')

@section('finance-content')
    <x-page-toolbar title="Accounts Receivable" meta="Create AR invoice">
        <x-slot:actions>
            <a href="{{ route('finance.ar.index', $department) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card">
        <form method="POST" action="{{ route('finance.ar.store', $department) }}" class="tich-form">
            @csrf
            <div class="tich-form__row">
                <label class="tich-form__label">Student</label>
                <select name="student_id" class="tich-form__input" required>
                    <option value="">Select student</option>
                </select>
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


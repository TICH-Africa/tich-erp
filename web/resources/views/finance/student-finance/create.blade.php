@extends('layouts.finance')

@section('title', 'Student Finance')

@section('finance-content')
    <x-page-toolbar title="Student Finance" meta="Create student invoice">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.index', $department) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card">
        <form method="POST" action="{{ route('finance.student-finance.store', $department) }}" class="tich-form">
            @csrf
            <div class="tich-form__row">
                <label class="tich-form__label">Student</label>
                <select name="student_id" class="tich-form__input" required>
                    <option value="">Select student</option>
                </select>
            </div>
            <div class="tich-form__row">
                <label class="tich-form__label">Fee structure</label>
                <select name="fee_structure_id" class="tich-form__input" required>
                    <option value="">Select fee structure</option>
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
                <label class="tich-form__label">Notes</label>
                <textarea name="notes" class="tich-form__input" rows="3"></textarea>
            </div>
            <button type="submit" class="tich-btn tich-btn-primary">Create invoice</button>
        </form>
    </div>
@endsection




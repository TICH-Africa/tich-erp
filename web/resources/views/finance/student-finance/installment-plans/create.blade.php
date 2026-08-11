@extends('layouts.finance')

@section('title', 'New Installment Plan')

@section('finance-content')
    <x-page-toolbar title="New Installment Plan" meta="Create a new installment payment plan">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.installment-plans.index', ['department' => $department->id]) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <article class="tich-card">
        <form method="POST" action="{{ route('finance.student-finance.installment-plans.store', ['department' => $department->id]) }}" class="tich-mt-4">
            @csrf
            <div class="tich-form-grid tich-form-grid--2">
                <div class="tich-form-group">
                    <label class="tich-label">Student</label>
                    <select name="student_id" class="tich-input" required>
                        <option value="">Select student</option>
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Invoice</label>
                    <select name="invoice_id" class="tich-input" required>
                        <option value="">Select invoice</option>
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Total Amount (KES)</label>
                    <input type="number" name="total_amount" class="tich-input" step="0.01" required placeholder="0.00" />
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Number of Installments</label>
                    <input type="number" name="installment_count" class="tich-input" min="2" max="12" value="3" required />
                </div>
            </div>

            <div class="tich-form-group tich-mt-4">
                <button type="submit" class="tich-btn tich-btn-primary">Create plan</button>
                <a href="{{ route('finance.student-finance.installment-plans.index', ['department' => $department->id]) }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </article>
@endsection



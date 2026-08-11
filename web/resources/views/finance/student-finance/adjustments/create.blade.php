@extends('layouts.finance')

@section('title', 'New Adjustment')

@section('finance-content')
    <x-page-toolbar title="New Adjustment" meta="Create a scholarship, bursary, or waiver adjustment">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.adjustments.index', ['department' => $department->id]) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <article class="tich-card">
        <form method="POST" action="{{ route('finance.student-finance.adjustments.store', ['department' => $department->id]) }}" class="tich-mt-4">
            @csrf
            <div class="tich-form-grid tich-form-grid--2">
                <div class="tich-form-group">
                    <label class="tich-label">Student</label>
                    <select name="student_id" class="tich-input" required>
                        <option value="">Select student</option>
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Adjustment Type</label>
                    <select name="adjustment_type" class="tich-input" required>
                        <option value="scholarship">Scholarship</option>
                        <option value="bursary">Bursary</option>
                        <option value="waiver">Waiver</option>
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Invoice (Optional)</label>
                    <select name="invoice_id" class="tich-input">
                        <option value="">Select invoice</option>
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Amount (KES)</label>
                    <input type="number" name="amount" class="tich-input" step="0.01" required placeholder="0.00" />
                </div>
            </div>

            <div class="tich-form-group tich-mt-4">
                <label class="tich-label">Reason</label>
                <textarea name="reason" class="tich-input" rows="3" required placeholder="Explain the reason for this adjustment..."></textarea>
            </div>

            <div class="tich-form-group tich-mt-4">
                <button type="submit" class="tich-btn tich-btn-primary">Submit adjustment</button>
                <a href="{{ route('finance.student-finance.adjustments.index', ['department' => $department->id]) }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </article>
@endsection



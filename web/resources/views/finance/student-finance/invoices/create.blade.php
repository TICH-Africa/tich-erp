@extends('layouts.finance')

@section('title', 'New Invoice')

@section('finance-content')
    <x-page-toolbar title="New Invoice" meta="Create a new student invoice">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.invoices.index', $department) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <article class="tich-card">
        <form method="POST" action="{{ route('finance.student-finance.invoices.store', $department) }}" class="tich-mt-4">
            @csrf
            <div class="tich-form-grid tich-form-grid--2">
                <div class="tich-form-group">
                    <label class="tich-label">Student</label>
                    <select name="student_id" class="tich-input" required>
                        <option value="">Select student</option>
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Fee Structure</label>
                    <select name="fee_structure_id" class="tich-input">
                        <option value="">Select fee structure</option>
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Invoice Type</label>
                    <select name="invoice_type" class="tich-input" required>
                        <option value="tuition">Tuition</option>
                        <option value="application">Application</option>
                        <option value="supplementary">Supplementary</option>
                        <option value="graduation">Graduation</option>
                        <option value="hostel">Hostel</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Due Date</label>
                    <input type="date" name="due_date" class="tich-input" required />
                </div>
            </div>

            <div class="tich-form-group tich-mt-4">
                <label class="tich-label">Description</label>
                <textarea name="description" class="tich-input" rows="3" required placeholder="Enter invoice description..."></textarea>
            </div>

            <div class="tich-form-group tich-mt-4">
                <button type="submit" class="tich-btn tich-btn-primary">Create invoice</button>
                <a href="{{ route('finance.student-finance.invoices.index', $department) }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </article>
@endsection



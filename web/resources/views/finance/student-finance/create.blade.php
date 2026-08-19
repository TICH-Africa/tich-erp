@extends('layouts.finance')

@section('title', 'Student Finance')

@section('finance-content')
    <x-page-toolbar title="Student Finance" meta="Create student invoice">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.index', $department) }}" class="tich-btn tich-btn-ghost">Back</a>
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

    <form method="POST" action="{{ route('finance.student-finance.store', $department) }}" class="tich-card tich-form-grid">
        @csrf
        <div class="tich-form-row">
            <label class="tich-label" for="student_id">Student <span class="tich-text--danger">*</span></label>
            <select id="student_id" name="student_id" class="tich-input" required>
                <option value="">Select student</option>
            </select>
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="fee_structure_id">Fee structure <span class="tich-text--danger">*</span></label>
            <select id="fee_structure_id" name="fee_structure_id" class="tich-input" required>
                <option value="">Select fee structure</option>
            </select>
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="amount">Amount (KES) <span class="tich-text--danger">*</span></label>
            <input type="number" id="amount" name="amount" class="tich-input" step="0.01" placeholder="0.00" required>
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="due_date">Due date <span class="tich-text--danger">*</span></label>
            <input type="date" id="due_date" name="due_date" class="tich-input" required>
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="notes">Notes</label>
            <textarea id="notes" name="notes" class="tich-input" rows="4" placeholder="Optional notes..."></textarea>
        </div>
        <div class="tich-form-row">
            <button type="submit" class="tich-btn tich-btn-primary">Create invoice</button>
            <a href="{{ route('finance.student-finance.index', $department) }}" class="tich-btn tich-btn-ghost" style="margin-left: 0.5rem;">Cancel</a>
        </div>
    </form>
@endsection

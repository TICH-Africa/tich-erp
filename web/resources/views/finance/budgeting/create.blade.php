@extends('layouts.finance')

@section('title', 'Create budget')

@section('finance-content')
    <x-page-toolbar title="Create budget" meta="Set up a new budget for a department">
        <x-slot:actions>
            <a href="{{ route('finance.budgeting.index', $department) }}" class="tich-btn tich-btn-ghost">Back</a>
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

    <form method="POST" action="{{ route('finance.budgeting.store', $department) }}" class="tich-card tich-form-grid">
        @csrf

        <div class="tich-form-row">
            <label class="tich-label" for="budget_name">Budget name <span class="tich-text--danger">*</span></label>
            <input type="text" id="budget_name" name="budget_name" class="tich-input" placeholder="e.g. Academics operations" value="{{ old('budget_name') }}" required>
        </div>

        <div class="tich-form-row">
            <label class="tich-label" for="budget_code">Budget code <span class="tich-text--danger">*</span></label>
            <input type="text" id="budget_code" name="budget_code" class="tich-input" placeholder="e.g. BGT-FIN-ACAD" value="{{ old('budget_code') }}" required>
        </div>

        <div class="tich-form-row">
            <label class="tich-label" for="budget_type">Budget cycle <span class="tich-text--danger">*</span></label>
            <select id="budget_type" name="budget_type" class="tich-input" required>
                <option value="annual" {{ old('budget_type') === 'annual' ? 'selected' : '' }}>Annual</option>
                <option value="quarterly" {{ old('budget_type') === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                <option value="monthly" {{ old('budget_type') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                <option value="weekly" {{ old('budget_type') === 'weekly' ? 'selected' : '' }}>Weekly</option>
            </select>
        </div>

        <div class="tich-form-row">
            <label class="tich-label" for="department_id">Department <span class="tich-text--danger">*</span></label>
            <select id="department_id" name="department_id" class="tich-input" required>
                <option value="">Select department</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->dept_name }}</option>
                @endforeach
            </select>
        </div>

        <div class="tich-form-row">
            <label class="tich-label" for="fiscal_year">Fiscal year <span class="tich-text--danger">*</span></label>
            <input type="number" id="fiscal_year" name="fiscal_year" class="tich-input" value="{{ old('fiscal_year', date('Y')) }}" required>
        </div>

        <div class="tich-form-row">
            <label class="tich-label" for="period_start">Period start <span class="tich-text--danger">*</span></label>
            <input type="text" id="period_start" name="period_start" class="tich-input" placeholder="dd/mm/yyyy" value="{{ old('period_start') }}" required>
        </div>

        <div class="tich-form-row">
            <label class="tich-label" for="period_end">Period end <span class="tich-text--danger">*</span></label>
            <input type="text" id="period_end" name="period_end" class="tich-input" placeholder="dd/mm/yyyy" value="{{ old('period_end') }}" required>
        </div>

        <div class="tich-form-row">
            <label class="tich-label" for="allocated_amount">Allocated amount (KES) <span class="tich-text--danger">*</span></label>
            <input type="number" step="0.01" min="0" id="allocated_amount" name="allocated_amount" class="tich-input" placeholder="0.00" value="{{ old('allocated_amount') }}" required>
        </div>

        <div class="tich-form-row">
            <label class="tich-label" for="notes">Notes</label>
            <textarea id="notes" name="notes" class="tich-input" rows="4" placeholder="Optional notes...">{{ old('notes') }}</textarea>
        </div>

        <div class="tich-form-row">
            <button type="submit" class="tich-btn tich-btn-primary">Create budget</button>
            <a href="{{ route('finance.budgeting.index', $department) }}" class="tich-btn tich-btn-ghost" style="margin-left: 0.5rem;">Cancel</a>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const formatDateInput = function (input) {
                input.addEventListener('blur', function () {
                    let value = input.value.replace(/[^\d]/g, '');
                    if (value.length === 8) {
                        input.value = value.slice(0, 2) + '/' + value.slice(2, 4) + '/' + value.slice(4, 8);
                    }
                });
            };

            document.querySelectorAll('input[name="period_start"], input[name="period_end"]').forEach(formatDateInput);
        });
    </script>
@endsection

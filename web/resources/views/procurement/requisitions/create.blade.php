@extends('layouts.procurement')

@section('title', 'New Requisition')

@section('procurement-content')
    <x-page-toolbar title="New Requisition" meta="Submit a new procurement requisition for approval">
        <x-slot:actions>
            <a href="{{ route('procurement.requisitions.index') }}" class="tich-btn tich-btn-ghost">Back</a>
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

    <form method="POST" action="{{ route('procurement.requisitions.store') }}" class="tich-mt-6" enctype="multipart/form-data">
        @csrf
        <div class="tich-card tich-form-stack">
            <div class="tich-grid tich-grid--2" style="gap:1rem;">
                <div class="tich-form-group">
                    <label class="tich-label" for="requesting_department_id">Requesting department <span class="tich-text--danger">*</span></label>
                    <select id="requesting_department_id" name="requesting_department_id" class="tich-input" required>
                        <option value="">Select department</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected(old('requesting_department_id') == $department->id)>{{ $department->dept_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label" for="request_date">Request date <span class="tich-text--danger">*</span></label>
                    <input type="text" id="request_date" name="request_date" class="tich-input" placeholder="dd/mm/yyyy" value="{{ old('request_date', now()->format('d/m/Y')) }}" required>
                </div>
            </div>

            <div class="tich-grid tich-grid--2" style="gap:1rem;">
                <div class="tich-form-group">
                    <label class="tich-label" for="requested_item">Requested item / service <span class="tich-text--danger">*</span></label>
                    <input type="text" id="requested_item" name="requested_item" class="tich-input" placeholder="e.g. Laptops, Cleaning services, Borehole works" value="{{ old('requested_item') }}" required maxlength="300">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label" for="budget_code">Budget code</label>
                    <select id="budget_code" name="budget_code" class="tich-input">
                        <option value="">None / Auto-select</option>
                        @foreach ($budgets as $budget)
                            <option value="{{ $budget->budget_code }}" @selected(old('budget_code') === $budget->budget_code)>{{ $budget->budget_code }} - {{ $budget->budget_name }} (FY{{ $budget->fiscal_year }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="tich-grid tich-grid--2" style="gap:1rem;">
                <div class="tich-form-group">
                    <label class="tich-label" for="budget_line">Budget line</label>
                    <input type="text" id="budget_line" name="budget_line" class="tich-input" placeholder="Optional budget line" value="{{ old('budget_line') }}">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label" for="estimated_unit_cost">Estimated unit cost (KES)</label>
                    <input type="number" id="estimated_unit_cost" name="estimated_unit_cost" class="tich-input" step="0.01" min="0" placeholder="0.00" value="{{ old('estimated_unit_cost') }}">
                </div>
            </div>

            <div class="tich-grid tich-grid--2" style="gap:1rem;">
                <div class="tich-form-group">
                    <label class="tich-label" for="quantity">Quantity</label>
                    <input type="number" id="quantity" name="quantity" class="tich-input" step="0.0001" min="0.0001" placeholder="1" value="{{ old('quantity') }}">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label" for="estimated_cost">Estimated total cost (KES) <span class="tich-text--danger">*</span></label>
                    <input type="number" id="estimated_cost" name="estimated_cost" class="tich-input" step="0.01" min="0" placeholder="0.00" value="{{ old('estimated_cost') }}" required>
                </div>
            </div>

            <div class="tich-form-group">
                <label class="tich-label" for="justification">Justification <span class="tich-text--danger">*</span></label>
                <textarea id="justification" name="justification" class="tich-input" rows="4" placeholder="Describe the purpose and business need for this requisition…" required>{{ old('justification') }}</textarea>
            </div>

            <div class="tich-form-group">
                <label class="tich-label" for="attachments">Attachments</label>
                <input type="file" id="attachments" name="attachments[]" class="tich-input" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,png">
                <p class="tich-caption tich-mt-1" style="margin:0;">Max 5 files, 5MB each. Accepted: PDF, DOC, DOCX, JPG, JPEG, PNG.</p>
            </div>
        </div>

        <div class="tich-mt-6">
            <button type="submit" class="tich-btn tich-btn-primary">Save requisition draft</button>
        </div>
    </form>
@endsection

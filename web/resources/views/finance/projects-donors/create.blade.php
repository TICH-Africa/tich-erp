@extends('layouts.finance')

@section('title', 'Projects & Donors')

@section('finance-content')
    <x-page-toolbar title="Projects & Donors" meta="Create project">
        <x-slot:actions>
            <a href="{{ route('finance.projects-donors.index') }}" class="tich-btn tich-btn-ghost">Back</a>
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

    <form method="POST" action="{{ route('finance.projects-donors.store') }}" class="tich-card tich-form-grid">
        @csrf
        <div class="tich-form-row">
            <label class="tich-label" for="name">Project name <span class="tich-text--danger">*</span></label>
            <input type="text" id="name" name="name" class="tich-input" placeholder="e.g. Community Health Outreach" value="{{ old('name') }}" required>
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="donor_id">Donor <span class="tich-text--danger">*</span></label>
            <select id="donor_id" name="donor_id" class="tich-input" required>
                <option value="">Select donor</option>
            </select>
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="budget_usd">Budget (USD) <span class="tich-text--danger">*</span></label>
            <input type="number" step="0.01" min="0" id="budget_usd" name="budget_usd" class="tich-input" placeholder="0.00" value="{{ old('budget_usd') }}" required>
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="start_date">Start date <span class="tich-text--danger">*</span></label>
            <input type="text" id="start_date" name="start_date" class="tich-input" placeholder="dd/mm/yyyy" value="{{ old('start_date') }}" required>
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="end_date">End date <span class="tich-text--danger">*</span></label>
            <input type="text" id="end_date" name="end_date" class="tich-input" placeholder="dd/mm/yyyy" value="{{ old('end_date') }}" required>
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="notes">Notes</label>
            <textarea id="notes" name="notes" class="tich-input" rows="4" placeholder="Optional notes...">{{ old('notes') }}</textarea>
        </div>
        <div class="tich-form-row">
            <button type="submit" class="tich-btn tich-btn-primary">Create project</button>
            <a href="{{ route('finance.projects-donors.index') }}" class="tich-btn tich-btn-ghost" style="margin-left: 0.5rem;">Cancel</a>
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

            document.querySelectorAll('input[name="start_date"], input[name="end_date"]').forEach(formatDateInput);
        });
    </script>
@endsection

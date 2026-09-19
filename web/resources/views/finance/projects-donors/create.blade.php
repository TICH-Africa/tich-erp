@extends('layouts.finance')

@section('title', 'Projects & Donors')

@section('finance-content')
    <x-page-toolbar title="Projects & Donors" meta="Create project">
        <x-slot:actions>
            <a href="{{ route('finance.projects-donors.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="uf-form">
        <form method="POST" action="{{ route('finance.projects-donors.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">PRJ · Projects &amp; donors</div>
                    <div class="uf-amount-bar__sum">Create project</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Project Details</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="name">Project name <span class="uf-req">*</span></label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                placeholder="e.g. Community Health Outreach"
                                value="{{ old('name') }}"
                                required
                                class="{{ $errors->has('name') ? 'is-invalid' : '' }}"
                            >
                            @error('name')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="donor_id">Donor <span class="uf-req">*</span></label>
                            <select
                                id="donor_id"
                                name="donor_id"
                                required
                                class="{{ $errors->has('donor_id') ? 'is-invalid' : '' }}"
                            >
                                <option value="">Select donor</option>
                            </select>
                            @error('donor_id')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="budget_usd">Budget (USD) <span class="uf-req">*</span></label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                id="budget_usd"
                                name="budget_usd"
                                placeholder="0.00"
                                value="{{ old('budget_usd') }}"
                                required
                                class="{{ $errors->has('budget_usd') ? 'is-invalid' : '' }}"
                            >
                            @error('budget_usd')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="start_date">Start date <span class="uf-req">*</span></label>
                            <input
                                type="text"
                                id="start_date"
                                name="start_date"
                                placeholder="dd/mm/yyyy"
                                value="{{ old('start_date') }}"
                                required
                                class="{{ $errors->has('start_date') ? 'is-invalid' : '' }}"
                            >
                            @error('start_date')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="end_date">End date <span class="uf-req">*</span></label>
                            <input
                                type="text"
                                id="end_date"
                                name="end_date"
                                placeholder="dd/mm/yyyy"
                                value="{{ old('end_date') }}"
                                required
                                class="{{ $errors->has('end_date') ? 'is-invalid' : '' }}"
                            >
                            @error('end_date')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="uf-field">
                        <label for="notes">Notes</label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="4"
                            placeholder="Optional notes..."
                            class="{{ $errors->has('notes') ? 'is-invalid' : '' }}"
                        >{{ old('notes') }}</textarea>
                        @error('notes')
                            <span class="uf-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Create project</button>
                        <a href="{{ route('finance.projects-donors.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>

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

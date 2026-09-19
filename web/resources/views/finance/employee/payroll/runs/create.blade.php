@extends('layouts.finance')

@section('title', 'New Payroll Run')

@section('finance-content')
    <x-page-toolbar title="New payroll run" meta="Calculate and save a monthly payroll batch from current staff records">
        <x-slot:actions>
            <a href="{{ route('finance.employee.payroll.runs.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="uf-form">
        <form method="post" action="{{ route('finance.employee.payroll.runs.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">PAY · Payroll run</div>
                    <div class="uf-amount-bar__sum">Monthly payroll batch</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Pay Period</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="pay_period_year">Pay period year <span class="uf-req">*</span></label>
                            <input
                                type="number"
                                id="pay_period_year"
                                name="pay_period_year"
                                value="{{ old('pay_period_year', $defaultYear) }}"
                                min="2020"
                                max="2100"
                                required
                                class="{{ $errors->has('pay_period_year') ? 'is-invalid' : '' }}"
                            >
                            @error('pay_period_year')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="pay_period_month">Pay period month <span class="uf-req">*</span></label>
                            <select
                                id="pay_period_month"
                                name="pay_period_month"
                                required
                                class="{{ $errors->has('pay_period_month') ? 'is-invalid' : '' }}"
                            >
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" @selected(old('pay_period_month', $defaultMonth) == $m)>{{ \Illuminate\Support\Carbon::create(null, $m, 1)->format('F') }}</option>
                                @endfor
                            </select>
                            @error('pay_period_month')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="uf-field">
                        <label for="notes">Notes</label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="2"
                            placeholder="e.g. March 2026 regular payroll"
                            class="{{ $errors->has('notes') ? 'is-invalid' : '' }}"
                        >{{ old('notes') }}</textarea>
                        @error('notes')
                            <span class="uf-error">{{ $message }}</span>
                        @enderror
                        <span class="uf-hint">Optional</span>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Create &amp; calculate</button>
                        <a href="{{ route('finance.employee.payroll.runs.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
@endsection

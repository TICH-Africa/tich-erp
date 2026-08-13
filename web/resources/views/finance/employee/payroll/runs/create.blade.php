@extends('layouts.finance')

@section('title', 'New Payroll Run')

@section('finance-content')
    <x-page-toolbar title="New payroll run" meta="Calculate and save a monthly payroll batch from current staff records">
        <x-slot:actions>
            <a href="{{ route('finance.employee.payroll.runs.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <article class="tich-card tich-mt-8">
        <form method="post" action="{{ route('finance.employee.payroll.runs.store') }}" class="tich-form-grid tich-form-grid--2">
            @csrf
            <div class="tich-form-group">
                <label class="tich-label">Pay period year</label>
                <input type="number" name="pay_period_year" class="tich-input" value="{{ old('pay_period_year', $defaultYear) }}" min="2020" max="2100" required>
            </div>
            <div class="tich-form-group">
                <label class="tich-label">Pay period month</label>
                <select name="pay_period_month" class="tich-input" required>
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" @selected(old('pay_period_month', $defaultMonth) == $m)>{{ \Illuminate\Support\Carbon::create(null, $m, 1)->format('F') }}</option>
                    @endfor
                </select>
            </div>
            <div class="tich-form-group" style="grid-column: 1 / -1;">
                <label class="tich-label">Notes <span class="tich-caption">(optional)</span></label>
                <textarea name="notes" class="tich-input" rows="2" placeholder="e.g. March 2026 regular payroll">{{ old('notes') }}</textarea>
            </div>
            <div class="tich-form-group" style="grid-column: 1 / -1;">
                <button type="submit" class="tich-btn tich-btn-primary">Create &amp; calculate</button>
            </div>
        </form>
    </article>
@endsection

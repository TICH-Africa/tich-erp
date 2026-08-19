@extends('layouts.finance')

@section('title', 'Payroll Integration')

@section('finance-content')
    <x-page-toolbar title="Payroll Integration" meta="Sync approved payroll data from HR/Payroll">
        <x-slot:actions>
            <a href="{{ route('finance.payroll-integration.index', $department) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <form method="POST" action="{{ route('finance.payroll-integration.sync', $department) }}" class="tich-card tich-form-grid">
        @csrf
        <div class="tich-form-row">
            <label class="tich-label" for="period">Payroll period <span class="tich-text--danger">*</span></label>
            <input type="month" id="period" name="period" class="tich-input" required>
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="source">Source <span class="tich-text--danger">*</span></label>
            <select id="source" name="source" class="tich-input" required>
                <option value="workpay">Workpay</option>
            </select>
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="gl_account_id">GL account <span class="tich-text--danger">*</span></label>
            <select id="gl_account_id" name="gl_account_id" class="tich-input" required>
                <option value="">Select GL account</option>
            </select>
        </div>
        <div class="tich-form-row">
            <button type="submit" class="tich-btn tich-btn-primary">Sync payroll</button>
            <a href="{{ route('finance.payroll-integration.index', $department) }}" class="tich-btn tich-btn-ghost" style="margin-left: 0.5rem;">Cancel</a>
        </div>
    </form>
@endsection

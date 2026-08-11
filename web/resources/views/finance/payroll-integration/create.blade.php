@extends('layouts.finance')

@section('title', 'Payroll Integration')

@section('department-content')
    <x-page-toolbar title="Payroll Integration" meta="Sync approved payroll data from HR/Payroll">
        <x-slot:actions>
            <a href="{{ route('finance.payroll-integration.index', $department) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card">
        <form method="POST" action="{{ route('finance.payroll-integration.sync', $department) }}" class="tich-form">
            @csrf
            <div class="tich-form__row">
                <label class="tich-form__label">Payroll period</label>
                <input type="month" name="period" class="tich-form__input" required />
            </div>
            <div class="tich-form__row">
                <label class="tich-form__label">Source</label>
                <select name="source" class="tich-form__input" required>
                    <option value="workpay">Workpay</option>
                </select>
            </div>
            <div class="tich-form__row">
                <label class="tich-form__label">GL account</label>
                <select name="gl_account_id" class="tich-form__input" required>
                    <option value="">Select GL account</option>
                </select>
            </div>
            <button type="submit" class="tich-btn tich-btn-primary">Sync payroll</button>
        </form>
    </div>
@endsection


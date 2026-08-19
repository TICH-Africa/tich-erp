@extends('layouts.finance')

@section('title', 'General Ledger')

@section('finance-content')
    <x-page-toolbar title="General Ledger" meta="Create journal entry">
        <x-slot:actions>
            <a href="{{ route('finance.gl.index', $department) }}" class="tich-btn tich-btn-ghost">Back</a>
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

    <form method="POST" action="{{ route('finance.gl.journal.store', $department) }}" class="tich-card tich-form-grid">
        @csrf
        <div class="tich-form-row">
            <label class="tich-label" for="date">Date <span class="tich-text--danger">*</span></label>
            <input type="date" id="date" name="date" class="tich-input" required>
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="description">Description <span class="tich-text--danger">*</span></label>
            <textarea id="description" name="description" class="tich-input" rows="4" placeholder="Journal entry description..." required></textarea>
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="debit_account_id">Debit account <span class="tich-text--danger">*</span></label>
            <select id="debit_account_id" name="debit_account_id" class="tich-input" required>
                <option value="">Select account</option>
            </select>
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="credit_account_id">Credit account <span class="tich-text--danger">*</span></label>
            <select id="credit_account_id" name="credit_account_id" class="tich-input" required>
                <option value="">Select account</option>
            </select>
        </div>
        <div class="tich-form-row">
            <label class="tich-label" for="amount">Amount (KES) <span class="tich-text--danger">*</span></label>
            <input type="number" step="0.01" min="0" id="amount" name="amount" class="tich-input" placeholder="0.00" required>
        </div>
        <div class="tich-form-row">
            <button type="submit" class="tich-btn tich-btn-primary">Post entry</button>
            <a href="{{ route('finance.gl.index', $department) }}" class="tich-btn tich-btn-ghost" style="margin-left: 0.5rem;">Cancel</a>
        </div>
    </form>
@endsection

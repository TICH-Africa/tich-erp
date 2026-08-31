@extends('layouts.finance')

@section('title', 'General Ledger')

@section('finance-content')
    <x-page-toolbar title="Journal entry" meta="{{ $entry->narration }}">
        <x-slot:actions>
            <a href="{{ route('finance.gl.index') }}" class="tich-btn tich-btn-ghost">Back to GL</a>
        </x-slot:actions>
    </x-page-toolbar>

    <article class="tich-card tich-mt-6">
        <dl style="display:grid; gap:0.85rem; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr));">
            <div><dt class="tich-caption">Date</dt><dd>{{ $entry->ledger_date?->format('d M Y') ?? '-' }}</dd></div>
            <div><dt class="tich-caption">Transaction type</dt><dd>{{ str_replace('_', ' ', $entry->transaction_type) }}</dd></div>
            <div><dt class="tich-caption">Source module</dt><dd>{{ str_replace('_', ' ', $entry->source_module) }}</dd></div>
            <div><dt class="tich-caption">Debit account</dt><dd>{{ $entry->debit_account_code ?? '-' }}</dd></div>
            <div><dt class="tich-caption">Credit account</dt><dd>{{ $entry->credit_account_code ?? '-' }}</dd></div>
            <div><dt class="tich-caption">Debit amount</dt><dd>KES {{ number_format((float) $entry->debit_amount, 2) }}</dd></div>
            <div><dt class="tich-caption">Credit amount</dt><dd>KES {{ number_format((float) $entry->credit_amount, 2) }}</dd></div>
            <div><dt class="tich-caption">Reference</dt><dd>{{ $entry->reference_table }} #{{ $entry->reference_id }}</dd></div>
            <div><dt class="tich-caption">Recorded by</dt><dd>{{ $entry->recorder?->fullName() ?? 'System' }}</dd></div>
        </dl>
        <p class="tich-mt-6"><strong>Narration:</strong> {{ $entry->narration }}</p>
    </article>
@endsection

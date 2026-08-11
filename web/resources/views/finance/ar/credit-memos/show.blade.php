@extends('layouts.finance')

@section('title', 'Credit memo')

@section('finance-content')
    <x-page-toolbar :title="$memo->credit_memo_number" meta="Credit memo details">
        <x-slot:actions>
            <a href="{{ route('finance.ar.credit-memos.index', $departmentParams) }}" class="tich-btn tich-btn-secondary">All credit memos</a>
        </x-slot:actions>
    </x-page-toolbar>

    <article class="tich-card tich-mt-8">
        <dl style="display:grid; grid-template-columns:10rem 1fr; gap:0.75rem 1rem;">
            <dt class="tich-caption">Student</dt>
            <dd>{{ $memo->student?->displayName() }} ({{ $memo->student?->registration_number }})</dd>
            <dt class="tich-caption">Invoice</dt>
            <dd>{{ $memo->invoice?->invoice_number }}</dd>
            <dt class="tich-caption">Amount</dt>
            <dd>KES {{ number_format((float) $memo->amount, 2) }}</dd>
            <dt class="tich-caption">Reason</dt>
            <dd>{{ $memo->reason }}</dd>
            <dt class="tich-caption">Issued by</dt>
            <dd>{{ $memo->issuer?->fullName() ?? '—' }}</dd>
            <dt class="tich-caption">Issued at</dt>
            <dd>{{ $memo->issued_at?->format('d M Y H:i') }}</dd>
        </dl>
    </article>
@endsection

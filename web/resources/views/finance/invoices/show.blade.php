@extends('layouts.finance')

@section('title', 'Invoice '.$invoice->invoice_number)

@section('finance-content')
    <x-page-toolbar title="{{ $invoice->invoice_number }}" meta="Issued {{ $invoice->issue_date?->format('d M Y') }}">
        <x-slot:actions>
            @if ($invoice->isPayable())
                <a href="{{ route('finance.payments.create', ['invoice_id' => $invoice->id]) }}" class="tich-btn tich-btn-primary">Record payment</a>
            @endif
            <form method="post" action="{{ route('finance.invoices.resend', $invoice) }}">
                @csrf
                <button type="submit" class="tich-btn tich-btn-ghost">Resend to portal/email</button>
            </form>
            @can('finance.invoices.manage')
                <a href="{{ route('finance.invoices.edit', $invoice) }}" class="tich-btn tich-btn-secondary">Edit</a>
            @endcan
        </x-slot:actions>
    </x-page-toolbar>

    @php($excessApplied = round(max((float) $invoice->amount_paid - (float) $invoice->payments->sum('amount'), 0), 2))

    <article class="tich-card tich-mb-8">
        <p><strong>Student:</strong> {{ $invoice->student?->displayName() }} ({{ $invoice->student?->registration_number }})</p>
        <p><strong>Programme:</strong> {{ $invoice->student?->program?->program_name }}</p>
        <p><strong>Type:</strong> {{ config('finance.invoice_types.'.$invoice->invoice_type, $invoice->invoice_type) }}</p>
        <p><strong>Description:</strong> {!! nl2br(e($invoice->description)) !!}</p>
        <p><strong>Amount:</strong> KES {{ number_format((float) $invoice->amount, 2) }}</p>
        <p><strong>Paid:</strong> KES {{ number_format((float) $invoice->amount_paid, 2) }}</p>
        @if ($excessApplied > 0)
            <p><strong>Excess payment applied:</strong> KES {{ number_format($excessApplied, 2) }}</p>
        @endif
        <p><strong>Balance:</strong> KES {{ number_format((float) $invoice->balance, 2) }}</p>
        <p><strong>Due date:</strong> {{ $invoice->due_date?->format('d M Y') }}</p>
        <p><strong>Status:</strong> {{ ucfirst($invoice->status) }}</p>
        <p><strong>Portal dispatch:</strong> {{ $invoice->is_sent_to_portal ? 'Sent '.$invoice->sent_at?->format('d M Y H:i') : 'Not sent' }}</p>
    </article>

    @if ($invoice->payments->isNotEmpty())
        <section>
            <h2 class="tich-h3">Payments</h2>
            <div class="tich-card tich-table-panel tich-mt-4">
                <table class="tich-admin-table">
                    <thead><tr><th>Payment</th><th>Date</th><th>Amount</th><th>Method</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($invoice->payments as $payment)
                            <tr>
                                <td>{{ $payment->payment_number }}</td>
                                <td>{{ $payment->payment_date?->format('d M Y') }}</td>
                                <td>KES {{ number_format((float) $payment->amount, 2) }}</td>
                                <td>{{ config('finance.payment_methods.'.$payment->payment_method, $payment->payment_method) }}</td>
                                <td>
                                    @can('finance.payments.manage')
                                        <a href="{{ route('finance.payments.edit', $payment) }}" class="tich-btn tich-btn-ghost">Edit</a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection

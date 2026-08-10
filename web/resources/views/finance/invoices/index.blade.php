@extends('layouts.finance')

@section('title', 'Invoices')

@section('finance-content')
    <x-page-toolbar title="Invoices" meta="Standardized invoice ledger with portal and email dispatch">
        <a href="{{ route('finance.invoices.create') }}" class="tich-btn tich-btn-primary">Generate invoice</a>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Student</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Balance</th>
                    <th>Due</th>
                    <th>Portal</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoices as $invoice)
                    <tr>
                        <td><a href="{{ route('finance.invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td>
                        <td>{{ $invoice->student?->displayName() }}</td>
                        <td>{{ config('finance.invoice_types.'.$invoice->invoice_type, $invoice->invoice_type) }}</td>
                        <td>KES {{ number_format((float) $invoice->amount, 2) }}</td>
                        <td>KES {{ number_format((float) $invoice->balance, 2) }}</td>
                        <td>{{ $invoice->due_date?->format('d M Y') }}</td>
                        <td>{{ $invoice->is_sent_to_portal ? 'Sent' : 'Pending' }}</td>
                        <td>{{ ucfirst($invoice->status) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="tich-caption">No invoices generated yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $invoices->links() }}
@endsection

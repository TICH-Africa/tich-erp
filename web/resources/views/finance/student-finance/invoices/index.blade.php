@extends('layouts.finance')

@section('title', 'Invoices')

@section('finance-content')
    <x-page-toolbar title="Invoices" meta="Student invoices for tuition, application, supplementary, graduation, hostel, and other charges">
<x-slot:actions>
            <a href="{{ route('finance.student-finance.invoices.create') }}" class="tich-btn tich-btn-primary">+ New invoice</a>
            <a href="{{ route('finance.student-finance.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Student</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr>
                            <td><strong>{{ $invoice->invoice_number }}</strong></td>
                            <td>
                                <strong>{{ $invoice->student->fullName() ?? 'N/A' }}</strong>
                                <p class="tich-caption">{{ $invoice->student->registration_number ?? 'N/A' }}</p>
                            </td>
                            <td class="tich-caption">{{ ucfirst($invoice->invoice_type) }}</td>
                            <td>KES {{ number_format($invoice->amount, 2) }}</td>
                            <td>KES {{ number_format($invoice->amount_paid, 2) }}</td>
                            <td><strong>KES {{ number_format($invoice->balance, 2) }}</strong></td>
                            <td>
                                <span class="tich-badge tich-badge--{{ match($invoice->status) {
                                    'issued' => 'info',
                                    'partial' => 'warning',
                                    'paid' => 'success',
                                    'overdue' => 'danger',
                                    'waived' => 'secondary',
                                    default => 'secondary',
                                } }}">
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            </td>
                            <td class="tich-caption">{{ $invoice->due_date?->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('finance.student-finance.invoices.show', ['id' => $invoice->id]) }}" class="tich-btn tich-btn-ghost">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="tich-table-empty">No invoices found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($invoices instanceof \Illuminate\Contracts\Pagination\Paginator && $invoices->hasPages())
            <div class="tich-mt-4">{{ $invoices->links() }}</div>
        @endif
    </div>
@endsection




@extends('layouts.finance')

@section('title', 'Receipts')

@section('finance-content')
    <x-page-toolbar title="Receipts" meta="Payment receipts">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.index', ['department' => $department->id]) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Receipt</th>
                        <th>Student</th>
                        <th>Invoice</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Issued At</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($receipts as $receipt)
                        <tr>
                            <td><strong>{{ $receipt->receipt_number }}</strong></td>
                            <td>
                                <strong>{{ $receipt->student->fullName() ?? 'N/A' }}</strong>
                                <p class="tich-caption">{{ $receipt->student->registration_number ?? 'N/A' }}</p>
                            </td>
                            <td class="tich-caption">{{ $receipt->invoice->invoice_number ?? 'N/A' }}</td>
                            <td>KES {{ number_format($receipt->amount, 2) }}</td>
                            <td class="tich-caption">{{ ucfirst($receipt->payment_method) }}</td>
                            <td class="tich-caption">{{ $receipt->payment_reference ?? '—' }}</td>
                            <td class="tich-caption">{{ $receipt->issued_at?->format('d M Y H:i') }}</td>
                            <td>
                                <a href="{{ route('finance.student-finance.receipts.show', ['department' => $department->id, 'id' => $receipt->id]) }}" class="tich-btn tich-btn-ghost">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="tich-table-empty">No receipts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($receipts instanceof \Illuminate\Contracts\Pagination\Paginator && $receipts->hasPages())
            <div class="tich-mt-4">{{ $receipts->links() }}</div>
        @endif
    </div>
@endsection




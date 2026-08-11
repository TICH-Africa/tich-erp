@extends('layouts.finance')

@section('title', 'Refunds')

@section('finance-content')
    <x-page-toolbar title="Refunds" meta="Student refund requests and processing">
<x-slot:actions>
            <a href="{{ route('finance.student-finance.refunds.create', ['department' => $department->id]) }}" class="tich-btn tich-btn-primary">+ New refund</a>
            <a href="{{ route('finance.student-finance.index', ['department' => $department->id]) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Refund</th>
                            <th>Student</th>
                            <th>Invoice</th>
                            <th>Amount</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Requested By</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($refunds as $refund)
                            <tr>
                                <td><strong>{{ $refund->refund_number }}</strong></td>
                                <td>
                                    <strong>{{ $refund->student->fullName() ?? 'N/A' }}</strong>
                                    <p class="tich-caption">{{ $refund->student->registration_number ?? 'N/A' }}</p>
                                </td>
                                <td class="tich-caption">{{ $refund->invoice->invoice_number ?? 'N/A' }}</td>
                                <td>KES {{ number_format($refund->amount, 2) }}</td>
                                <td>{{ $refund->reason }}</td>
                                <td>
                                    <span class="tich-badge tich-badge--{{ match($refund->status) {
                                        'pending' => 'warning',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'processed' => 'info',
                                        default => 'secondary',
                                    } }}">
                                        {{ ucfirst($refund->status) }}
                                    </span>
                                </td>
                                <td class="tich-caption">{{ $refund->requestedBy?->fullName() ?? ($refund->requested_by ?? 'N/A') }}</td>
                                <td class="tich-caption">{{ $refund->created_at?->format('d M Y') }}</td>
                                <td>
                                    <a href="{{ route('finance.student-finance.refunds.show', ['department' => $department->id, 'id' => $refund->id]) }}" class="tich-btn tich-btn-ghost">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="tich-table-empty">No refunds found.</td>
                            </tr>
                        @endforelse
                    </tbody>
            </table>
        </div>

        @if ($refunds instanceof \Illuminate\Contracts\Pagination\Paginator && $refunds->hasPages())
            <div class="tich-mt-4">{{ $refunds->links() }}</div>
        @endif
    </div>
@endsection




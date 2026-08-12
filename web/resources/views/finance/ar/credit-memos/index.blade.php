@extends('layouts.finance')

@section('title', 'Credit memos')

@section('finance-content')
    <x-page-toolbar title="Credit memos" meta="Formal credits against student invoices">
        <x-slot:actions>
            <a href="{{ route('finance.ar.credit-memos.create', $departmentParams) }}" class="tich-btn tich-btn-primary">+ Issue credit memo</a>
            <a href="{{ route('finance.ar.index', $departmentParams) }}" class="tich-btn tich-btn-secondary">Back to AR</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mt-8">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Memo</th>
                    <th>Student</th>
                    <th>Invoice</th>
                    <th>Amount</th>
                    <th>Issued</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($memos as $memo)
                    <tr>
                        <td>{{ $memo->credit_memo_number }}</td>
                        <td>{{ $memo->student?->displayName() }}</td>
                        <td>{{ $memo->invoice?->invoice_number }}</td>
                        <td>KES {{ number_format((float) $memo->amount, 2) }}</td>
                        <td>{{ $memo->issued_at?->format('d M Y') }}</td>
                        <td><a href="{{ route('finance.ar.credit-memos.show', ['department' => $departmentParams['department'] ?? $department->id, 'creditMemo' => $memo->id]) }}" class="tich-link">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="tich-table-empty">No credit memos yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $memos->links() }}
@endsection

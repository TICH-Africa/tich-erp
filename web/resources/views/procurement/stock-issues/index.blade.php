@extends('layouts.procurement')

@section('title', 'Stock issues')

@section('procurement-content')
    <x-page-toolbar title="Stock issues" meta="Store issue requests and approvals">
        <x-slot:actions>
            <a href="{{ route('procurement.stock-issues.create') }}" class="tich-btn tich-btn-primary">+ Raise issue</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th class="tich-col-num">#</th>
                        <th>Item</th>
                        <th>Department</th>
                        <th>Qty</th>
                        <th>Requested</th>
                        <th>Approval</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($issues as $issue)
                        <tr>
                            <td class="tich-col-num">{{ $issues->firstItem() + $loop->index }}</td>
                            <td><a href="{{ route('procurement.inventory-items.show', $issue->item) }}" class="tich-link">{{ $issue->item?->item_name ?? '-' }}</a></td>
                            <td>{{ $issue->department?->dept_name ?? '-' }}</td>
                            <td>{{ $issue->quantity }}</td>
                            <td>{{ $issue->created_at?->format('d M Y') ?? '-' }}</td>
                            <td>{{ ucfirst($issue->approval_status) }}</td>
                            <td>{{ ucfirst($issue->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="tich-table-empty">None.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('partials.pagination-footer', ['paginator' => $issues])
@endsection

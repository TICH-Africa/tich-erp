@extends('layouts.procurement')

@section('title', 'RFQs')

@section('procurement-content')
    <x-page-toolbar title="RFQs" meta="Request for quotations and awards">
        <x-slot:actions>
            <a href="{{ route('procurement.rfqs.create') }}" class="tich-btn tich-btn-primary">+ New RFQ</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th class="tich-col-num">#</th>
                        <th>RFQ #</th>
                        <th>Subject</th>
                        <th>Category</th>
                        <th>Requisition</th>
                        <th>Suppliers</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rfqs as $rfq)
                        <tr>
                            <td class="tich-col-num">{{ $rfqs->firstItem() + $loop->index }}</td>
                            <td>{{ $rfq->rfq_number }}</td>
                            <td><a href="{{ route('procurement.rfqs.show', $rfq) }}" class="tich-link">{{ Str::limit($rfq->title ?? $rfq->item_description, 60) }}</a></td>
                            <td>{{ ucfirst($rfq->category ?? '-') }}</td>
                            <td>{{ $rfq->requisition_id ? 'PR/'.$rfq->requisition_id : '-' }}</td>
                            <td>{{ $rfq->suppliers->count() }}</td>
                            <td>
                                <x-status-badge :status="$rfq->status ?? 'draft'" />
                            </td>
                            <td>
                                <a href="{{ route('procurement.rfqs.show', $rfq) }}" class="tich-link">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="tich-table-empty">No RFQs yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('partials.pagination-footer', ['paginator' => $rfqs])
@endsection

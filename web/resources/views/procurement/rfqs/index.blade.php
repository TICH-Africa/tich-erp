@extends('layouts.procurement')

@section('title', 'RFQs')

@section('procurement-content')
    <x-page-toolbar title="RFQs" meta="Request for quotations and awards">
        <x-slot:actions>
            <a href="{{ route('procurement.rfqs.create') }}" class="tich-btn tich-btn-primary">+ New RFQ</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--4 tich-mt-6">
        <article class="tich-card tich-stat">
            <p class="tich-caption">Draft</p>
            <p class="tich-stat__value">{{ number_format($stats['draft'] ?? 0) }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Open</p>
            <p class="tich-stat__value">{{ number_format($stats['published'] ?? 0) }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Evaluated</p>
            <p class="tich-stat__value">{{ number_format($stats['evaluated'] ?? 0) }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Awarded</p>
            <p class="tich-stat__value">{{ number_format($stats['awarded'] ?? 0) }}</p>
        </article>
    </div>

    <div class="tich-card tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
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
                            <td>{{ $rfq->rfq_number }}</td>
                            <td><a href="{{ route('procurement.rfqs.show', $rfq) }}" class="tich-link">{{ Str::limit($rfq->title ?? $rfq->item_description, 60) }}</a></td>
                            <td>{{ ucfirst($rfq->category ?? '-') }}</td>
                            <td>{{ $rfq->requisition_id ? 'PR/'.$rfq->requisition_id : '-' }}</td>
                            <td>{{ $rfq->suppliers->count() }}</td>
                            <td>
                                @php($state = $rfq->status ?? 'draft')
                                <span class="tich-badge tich-badge--{{ $state === 'awarded' ? 'success' : ($state === 'published' ? 'warning' : ($state === 'evaluated' ? 'info' : 'secondary')) }}">
                                    {{ ucfirst($state) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('procurement.rfqs.show', $rfq) }}" class="tich-link">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="tich-table-empty">No RFQs yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="tich-mt-4">{{ $rfqs->links() }}</div>
@endsection

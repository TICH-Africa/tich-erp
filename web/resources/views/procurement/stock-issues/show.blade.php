@extends('layouts.procurement')

@section('title', 'Stock issue ' . $issue->id)

@section('procurement-content')
    <x-page-toolbar title="Stock issue" meta="{{ $issue->item?->item_name ?? '-' }}">
        <x-slot:actions>
            @if($issue->approval_status === 'pending')
                <form method="POST" action="{{ route('procurement.stock-issues.approve', $issue) }}" class="tich-inline-form">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-success">Approve</button>
                </form>
            @endif
            <a href="{{ route('procurement.stock-issues.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--2 tich-mt-6" style="gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3" style="margin-top:0;">Issue</h2>
            <dl class="tich-dl">
                <dt>Item</dt><dd>{{ $issue->item?->item_name ?? '-' }}</dd>
                <dt>Quantity</dt><dd>{{ $issue->quantity }}</dd>
                <dt>Total cost</dt><dd>KES {{ number_format((float) $issue->total_cost, 2) }}</dd>
                <dt>Department</dt><dd>{{ $issue->department?->dept_name ?? '-' }}</dd>
                <dt>Requested by</dt><dd>{{ $issue->requestedBy?->full_name ?? '-' }}</dd>
                <dt>Approval</dt><dd>{{ ucfirst($issue->approval_status) }}</dd>
                <dt>Status</dt><dd>{{ ucfirst($issue->status) }}</dd>
            </dl>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3" style="margin-top:0;">Details</h2>
            <dl class="tich-dl">
                <dt>Reason</dt><dd>{{ $issue->reason }}</dd>
                <dt>Notes</dt><dd>{{ $issue->notes ?? '-' }}</dd>
                <dt>Approved by</dt><dd>{{ $issue->approvedBy?->full_name ?? '-' }}</dd>
            </dl>
        </article>
    </div>
@endsection

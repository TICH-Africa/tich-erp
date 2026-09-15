@extends('layouts.procurement')

@section('title', 'Movement ' . $movement->asset->asset_number)

@section('procurement-content')
    <x-page-toolbar title="Asset movement" meta="{{ $movement->asset->asset_name }}">
        <x-slot:actions>
            @if($movement->approval_status === 'pending')
                <form method="POST" action="{{ route('procurement.asset-movements.approve', $movement) }}" class="tich-inline-form">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-success">Approve</button>
                </form>
            @endif
            <a href="{{ route('procurement.asset-movements.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--2 tich-mt-6" style="gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3" style="margin-top:0;">Request</h2>
            <dl class="tich-dl">
                <dt>Asset</dt><dd><a href="{{ route('procurement.assets.show', $movement->asset) }}" class="tich-link">{{ $movement->asset->asset_name }}</a></dd>
                <dt>From</dt><dd>{{ $movement->from_location ?? '-' }}</dd>
                <dt>To</dt><dd>{{ $movement->to_location ?? '-' }}</dd>
                <dt>Date</dt><dd>{{ $movement->movement_date?->format('d M Y') ?? '-' }}</dd>
                <dt>Type</dt><dd>{{ $movement->movement_type }}</dd>
                <dt>Requester</dt><dd>{{ $movement->requestedBy?->full_name ?? '-' }}</dd>
                <dt>Reason</dt><dd>{{ $movement->reason }}</dd>
                <dt>Approval</dt><dd>{{ ucfirst($movement->approval_status) }}</dd>
            </dl>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3" style="margin-top:0;">Notes</h2>
            <p class="tich-text">{{ $movement->notes ?? '-' }}</p>
            @if($movement->approval_status === 'approved')
                <p class="tich-text">Approved by {{ $movement->approvedBy?->full_name ?? '-' }} on {{ $movement->approved_at?->format('d M Y') ?? '-' }}.</p>
            @endif
        </article>
    </div>
@endsection

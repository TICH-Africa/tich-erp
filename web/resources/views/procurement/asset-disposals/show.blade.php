@extends('layouts.procurement')

@section('title', 'Disposal ' . $record->id)

@section('procurement-content')
    @php
        $assetLabel = $record->asset?->asset_name ?? ('Asset #'.($record->asset_id ?: '—'));
    @endphp
    <x-page-toolbar title="Disposal request" :meta="$assetLabel">
        <x-slot:actions>
            @if($record->approval_status === 'pending')
                <form method="POST" action="{{ route('procurement.asset-disposals.approve', $record) }}" class="tich-inline-form">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-success">Approve</button>
                </form>
            @endif
            <a href="{{ route('procurement.asset-disposals.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--2 tich-mt-6" style="gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3" style="margin-top:0;">Request</h2>
            <dl class="tich-dl">
                <dt>Asset</dt>
                <dd>
                    @if ($record->asset)
                        <a href="{{ route('procurement.assets.show', $record->asset) }}" class="tich-link">{{ $record->asset->asset_name }}</a>
                    @else
                        <span class="tich-caption">Asset missing (id {{ $record->asset_id ?? '—' }})</span>
                    @endif
                </dd>
                <dt>Type</dt><dd>{{ $record->disposal_type }}</dd>
                <dt>Value</dt><dd>KES {{ number_format((float) $record->disposed_value, 2) }}</dd>
                <dt>Reason</dt><dd>{{ $record->reason }}</dd>
                <dt>Requested by</dt><dd>{{ $record->requestedBy?->fullName() ?? '-' }}</dd>
                <dt>Approval</dt><dd>{{ ucfirst($record->approval_status) }}</dd>
                <dt>Status</dt><dd>{{ ucfirst($record->status) }}</dd>
            </dl>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3" style="margin-top:0;">Details</h2>
            <dl class="tich-dl">
                <dt>Disposal date</dt><dd>{{ $record->disposal_date?->format('d M Y') ?? '-' }}</dd>
                <dt>Details</dt><dd>{{ $record->disposal_details ?? '-' }}</dd>
                <dt>Approved by</dt><dd>{{ $record->approvedBy?->fullName() ?? '-' }}</dd>
            </dl>
        </article>
    </div>
@endsection

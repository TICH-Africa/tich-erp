@extends('layouts.procurement')

@section('title', 'Verification ' . $record->id)

@section('procurement-content')
    <x-page-toolbar title="Asset verification" meta="{{ $record->asset->asset_name }}">
        <x-slot:actions>
            @if($record->status !== 'reviewed')
                <form method="POST" action="{{ route('procurement.asset-audits.review', $record) }}" class="tich-inline-form">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-success">Review</button>
                </form>
            @endif
            <a href="{{ route('procurement.asset-audits.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--2 tich-mt-6" style="gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3" style="margin-top:0;">Verification</h2>
            <dl class="tich-dl">
                <dt>Asset</dt><dd><a href="{{ route('procurement.assets.show', $record->asset) }}" class="tich-link">{{ $record->asset->asset_name }}</a></dd>
                <dt>Auditor</dt><dd>{{ $record->auditor?->full_name ?? '-' }}</dd>
                <dt>Status</dt><dd>{{ $record->verification_status }}</dd>
                <dt>Condition</dt><dd>{{ $record->condition }}</dd>
                <dt>Location verified</dt><dd>{{ $record->location_verified ? 'Yes' : 'No' }}</dd>
                <dt>Custodian verified</dt><dd>{{ $record->custodian_verified ? 'Yes' : 'No' }}</dd>
                <dt>Submitted</dt><dd>{{ $record->submitted_at?->format('d M Y') ?? '-' }}</dd>
                <dt>Notes</dt><dd>{{ $record->notes ?? '-' }}</dd>
            </dl>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3" style="margin-top:0;">Review</h2>
            @if($record->status === 'reviewed')
                <dl class="tich-dl">
                    <dt>Reviewed by</dt><dd>{{ $record->reviewer?->full_name ?? '-' }}</dd>
                    <dt>Reviewed</dt><dd>{{ $record->reviewed_at?->format('d M Y') ?? '-' }}</dd>
                    <dt>Notes</dt><dd>{{ $record->review_notes ?? '-' }}</dd>
                </dl>
            @else
                <p class="tich-text">Pending finance/procurement review.</p>
            @endif
        </article>
    </div>
@endsection

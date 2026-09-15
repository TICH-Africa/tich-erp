@extends('layouts.procurement')

@section('title', 'Maintenance ' . $record->id)

@section('procurement-content')
    <x-page-toolbar title="Maintenance record" meta="{{ $record->asset->asset_name }}">
        <x-slot:actions>
            @if($record->status !== 'completed')
                <form method="POST" action="{{ route('procurement.asset-maintenance.complete', $record) }}" class="tich-inline-form">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-success">Complete</button>
                </form>
            @endif
            <a href="{{ route('procurement.asset-maintenance.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--2 tich-mt-6" style="gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3" style="margin-top:0;">Record</h2>
            <dl class="tich-dl">
                <dt>Asset</dt><dd><a href="{{ route('procurement.assets.show', $record->asset) }}" class="tich-link">{{ $record->asset->asset_name }}</a></dd>
                <dt>Type</dt><dd>{{ $record->maintenance_type }}</dd>
                <dt>Priority</dt><dd>{{ $record->priority }}</dd>
                <dt>Scheduled</dt><dd>{{ $record->scheduled_date?->format('d M Y') ?? '-' }}</dd>
                <dt>Fault</dt><dd>{{ $record->fault_description }}</dd>
                <dt>Status</dt><dd>{{ ucfirst($record->status) }}</dd>
            </dl>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3" style="margin-top:0;">Completion</h2>
            @if($record->status === 'completed')
                <dl class="tich-dl">
                    <dt>Completed</dt><dd>{{ $record->completed_date?->format('d M Y') ?? '-' }}</dd>
                    <dt>Work done</dt><dd>{{ $record->work_done ?? '-' }}</dd>
                    <dt>Parts used</dt><dd>{{ $record->parts_used ? json_encode($record->parts_used) : '-' }}</dd>
                    <dt>Total cost</dt><dd>KES {{ number_format((float) ($record->parts_cost + $record->labour_cost), 2) }}</dd>
                    <dt>Technician</dt><dd>{{ $record->technician_name ?? '-' }}</dd>
                    <dt>Completed by</dt><dd>{{ $record->completedBy?->full_name ?? '-' }}</dd>
                </dl>
            @else
                <p class="tich-text">Not yet completed.</p>
            @endif
        </article>
    </div>
@endsection

@extends('layouts.procurement')

@section('title', 'Asset audits')

@section('procurement-content')
    <x-page-toolbar title="Annual asset audits" meta="HOD physical verification">
        <x-slot:actions>
            <a href="{{ route('procurement.asset-audits.create') }}" class="tich-btn tich-btn-primary">+ New verification</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead><tr><th>Asset</th><th>Auditor</th><th>Verification</th><th>Submitted</th><th>Review</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($records as $record)
                        <tr>
                            <td><a href="{{ route('procurement.assets.show', $record->asset) }}" class="tich-link">{{ $record->asset->asset_name }}</a></td>
                            <td>{{ $record->auditor?->full_name ?? '-' }}</td>
                            <td>{{ $record->verification_status }}</td>
                            <td>{{ $record->submitted_at?->format('d M Y') ?? '-' }}</td>
                            <td>{{ $record->reviewer?->full_name ?? '-' }}</td>
                            <td>{{ ucfirst($record->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="tich-table-empty">None.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="tich-mt-4">{{ $records->links() }}</div>
@endsection

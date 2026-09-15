@extends('layouts.procurement')

@section('title', 'Asset disposals')

@section('procurement-content')
    <x-page-toolbar title="Asset disposals" meta="Write-off, donation, auction">
        <x-slot:actions>
            <a href="{{ route('procurement.asset-disposals.create') }}" class="tich-btn tich-btn-primary">+ New disposal</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead><tr><th>Asset</th><th>Type</th><th>Value</th><th>Requested</th><th>Approval</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($records as $record)
                        <tr>
                            <td><a href="{{ route('procurement.assets.show', $record->asset) }}" class="tich-link">{{ $record->asset->asset_name }}</a></td>
                            <td>{{ $record->disposal_type }}</td>
                            <td>KES {{ number_format((float) $record->disposed_value, 2) }}</td>
                            <td>{{ $record->created_at?->format('d M Y') ?? '-' }}</td>
                            <td>{{ ucfirst($record->approval_status) }}</td>
                            <td>{{ ucfirst($record->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="tich-table-empty">No requests.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="tich-mt-4">{{ $records->links() }}</div>
@endsection

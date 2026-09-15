@extends('layouts.procurement')

@section('title', 'Maintenance history')

@section('procurement-content')
    <x-page-toolbar title="Maintenance history" meta="Scheduled and ad-hoc repairs">
        <x-slot:actions>
            <a href="{{ route('procurement.asset-maintenance.create') }}" class="tich-btn tich-btn-primary">+ Schedule</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead><tr><th>Asset</th><th>Type</th><th>Priority</th><th>Scheduled</th><th>Completed</th><th>Cost</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($records as $record)
                        <tr>
                            <td><a href="{{ route('procurement.assets.show', $record->asset) }}" class="tich-link">{{ $record->asset->asset_name }}</a></td>
                            <td>{{ $record->maintenance_type }}</td>
                            <td>{{ $record->priority }}</td>
                            <td>{{ $record->scheduled_date?->format('d M Y') ?? '-' }}</td>
                            <td>{{ $record->completed_date?->format('d M Y') ?? '-' }}</td>
                            <td>KES {{ number_format((float) $record->parts_cost + (float) $record->labour_cost, 2) }}</td>
                            <td>{{ ucfirst($record->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="tich-table-empty">No records.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="tich-mt-4">{{ $records->links() }}</div>
@endsection

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
                <thead>
                    <tr>
                        <th class="tich-col-num">#</th>
                        <th>Asset</th>
                        <th>Type</th>
                        <th>Value</th>
                        <th>Requested</th>
                        <th>Approval</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        <tr>
                            <td class="tich-col-num">{{ $records->firstItem() + $loop->index }}</td>
                            <td>
                                @if ($record->asset)
                                    <a href="{{ route('procurement.asset-disposals.show', $record) }}" class="tich-link">{{ $record->asset->asset_name }}</a>
                                @else
                                    <a href="{{ route('procurement.asset-disposals.show', $record) }}" class="tich-link">Request #{{ $record->id }}</a>
                                @endif
                            </td>
                            <td>{{ $record->disposal_type }}</td>
                            <td>KES {{ number_format((float) $record->disposed_value, 2) }}</td>
                            <td>{{ $record->created_at?->format('d M Y') ?? '-' }}</td>
                            <td>{{ ucfirst($record->approval_status) }}</td>
                            <td>{{ ucfirst($record->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="tich-table-empty">No requests.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('partials.pagination-footer', ['paginator' => $records])
@endsection

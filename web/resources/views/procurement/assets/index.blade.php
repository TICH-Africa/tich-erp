@extends('layouts.procurement')

@section('title', 'Assets')

@section('procurement-content')
    <x-page-toolbar title="Asset registry" meta="Fixed assets, tagging, location, maintenance">
        <x-slot:actions>
            <a href="{{ route('procurement.assets.create') }}" class="tich-btn tich-btn-primary">+ Register asset</a>
        </x-slot:actions>
    </x-page-toolbar>

    <form method="get" class="tich-filter-bar tich-mt-6 tich-mb-4">
        <input type="search" name="search" value="{{ $search ?? request('search') }}" class="tich-input" placeholder="Search asset name, number, category…">
        <select name="category" class="tich-input"><option value="">All categories</option></select>
        <select name="status" class="tich-input"><option value="">All statuses</option></select>
        @if(request()->filled('per_page'))
            <input type="hidden" name="per_page" value="{{ request('per_page') }}">
        @endif
        <button type="submit" class="tich-btn tich-btn-secondary">Filter</button>
    </form>

    <div class="tich-card tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th class="tich-col-num">#</th>
                        <th>Asset #</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Custodian</th>
                        <th>Location</th>
                        <th>Value</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assets as $asset)
                        <tr>
                            <td class="tich-col-num">{{ $assets->firstItem() + $loop->index }}</td>
                            <td><a href="{{ route('procurement.assets.show', $asset) }}" class="tich-link">{{ $asset->asset_number }}</a></td>
                            <td>{{ $asset->asset_name }}</td>
                            <td>{{ $asset->asset_category }}</td>
                            <td>{{ $asset->custodian?->first_name ?? '' }} {{ $asset->custodian?->surname ?? '' }}</td>
                            <td>{{ $asset->location_name ?? '-' }}</td>
                            <td>KES {{ number_format((float) $asset->acquisition_cost, 2) }}</td>
                            <td>{{ ucfirst($asset->asset_status) }}</td>
                            <td><a href="{{ route('procurement.assets.show', $asset) }}" class="tich-link">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="tich-table-empty">No assets found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('partials.pagination-footer', ['paginator' => $assets])
@endsection

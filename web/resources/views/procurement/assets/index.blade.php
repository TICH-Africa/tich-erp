@extends('layouts.procurement')

@section('title', 'Assets')

@section('procurement-content')
    <x-page-toolbar title="Asset registry" meta="Fixed assets, tagging, location, maintenance">
        <x-slot:actions>
            <a href="{{ route('procurement.assets.create') }}" class="tich-btn tich-btn-primary">+ Register asset</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--4 tich-mt-6">
        <article class="tich-card tich-stat"><p class="tich-caption">Total assets</p><p class="tich-stat__value">{{ $stats['total'] }}</p></article>
        <article class="tich-card tich-stat"><p class="tich-caption">Active</p><p class="tich-stat__value">{{ $stats['active'] }}</p></article>
        <article class="tich-card tich-stat"><p class="tich-caption">Under maintenance</p><p class="tich-stat__value">{{ $stats['maintenance'] }}</p></article>
        <article class="tich-card tich-stat"><p class="tich-caption">Total cost</p><p class="tich-stat__value">KES {{ number_format((float) $stats['total_cost'], 2) }}</p></article>
    </div>

    <form method="get" class="tich-filter-bar tich-mt-6 tich-mb-4">
        <input type="search" name="search" value="{{ $search ?? '' }}" class="tich-input" placeholder="Search asset name, number, category…">
        <select name="category" class="tich-input"><option value="">All categories</option></select>
        <select name="status" class="tich-input"><option value="">All statuses</option></select>
        <button type="submit" class="tich-btn tich-btn-secondary">Filter</button>
    </form>

    <div class="tich-card tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead><tr><th>Asset #</th><th>Name</th><th>Category</th><th>Custodian</th><th>Location</th><th>Value</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($assets as $asset)
                        <tr>
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
                        <tr><td colspan="8" class="tich-table-empty">No assets found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="tich-mt-4">{{ $assets->links() }}</div>
@endsection

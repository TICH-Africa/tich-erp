@extends('layouts.procurement')

@section('title', 'Supplier Registry')

@section('procurement-content')
    <x-page-toolbar title="Supplier Registry" meta="Profiled, rated, and categorised suppliers">
        <x-slot:actions>
            <a href="{{ route('procurement.suppliers.create') }}" class="tich-btn tich-btn-primary">+ Register supplier</a>
        </x-slot:actions>
    </x-page-toolbar>

    <form method="get" class="tich-filter-bar tich-mt-6 tich-mb-4">
        <input type="search" name="search" value="{{ $search }}" class="tich-input" placeholder="Search supplier name, code, email…">
        <select name="status" class="tich-input">
            <option value="">All compliance statuses</option>
            <option value="pending" @selected($status === 'pending')>Pending</option>
            <option value="approved" @selected($status === 'approved')>Approved</option>
            <option value="rejected" @selected($status === 'rejected')>Rejected</option>
            <option value="under_review" @selected($status === 'under_review')>Under review</option>
        </select>
        <select name="category" class="tich-input">
            <option value="">All categories</option>
            <option value="goods" @selected($category === 'goods')>Goods</option>
            <option value="services" @selected($category === 'services')>Services</option>
            <option value="works" @selected($category === 'works')>Works</option>
        </select>
        @if(request()->filled('per_page'))
            <input type="hidden" name="per_page" value="{{ request('per_page') }}">
        @endif
        <button type="submit" class="tich-btn tich-btn-secondary">Filter</button>
        @if($search || $status || $category)
            <a href="{{ route('procurement.suppliers.index') }}" class="tich-btn tich-btn-ghost">Clear</a>
        @endif
    </form>

    <div class="tich-card tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th class="tich-col-num">#</th>
                        <th>Code</th>
                        <th>Supplier</th>
                        <th>Category</th>
                        <th>Risk</th>
                        <th>Performance</th>
                        <th>Compliance</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr>
                            <td class="tich-col-num">{{ $suppliers->firstItem() + $loop->index }}</td>
                            <td>{{ $supplier->supplier_code }}</td>
                            <td>
                                <a href="{{ route('procurement.suppliers.show', $supplier) }}" class="tich-link">{{ $supplier->supplier_name }}</a>
                                <p class="tich-caption" style="margin:0;">{{ $supplier->email }}</p>
                            </td>
                            <td>{{ ucfirst($supplier->supplier_category ?? '-') }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ $supplier->risk_rating === 'low' ? 'success' : ($supplier->risk_rating === 'high' ? 'danger' : 'secondary') }}">
                                    {{ ucfirst($supplier->risk_rating ?? 'medium') }}
                                </span>
                            </td>
                            <td>{{ number_format((float) $supplier->performance_score, 2) }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $supplier->compliance_status)) }}</td>
                            <td>
                                @if($supplier->isBlacklisted())
                                    <span class="tich-badge tich-badge--danger">Blacklisted</span>
                                @elseif($supplier->isActive())
                                    <span class="tich-badge tich-badge--success">Active</span>
                                @else
                                    <span class="tich-badge tich-badge--secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('procurement.suppliers.show', $supplier) }}" class="tich-link">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="tich-table-empty">No suppliers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('partials.pagination-footer', ['paginator' => $suppliers])
@endsection

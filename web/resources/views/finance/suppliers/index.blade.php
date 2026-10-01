@extends('layouts.finance')

@section('title', 'Suppliers')

@section('finance-content')
    <x-page-toolbar title="Suppliers" meta="Supplier master record registered by Procurement, with compliance status, KRA tax status, payables outstanding and payment details for Finance">
        <x-slot:actions>
            <a href="{{ route('finance.suppliers.index') }}" class="tich-btn tich-btn-ghost">Reset</a>
            <a href="{{ route('finance.ap.create') }}" class="tich-btn tich-btn-primary">+ New invoice</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--3 tich-mt-6">
        <article class="tich-card tich-stat">
            <p class="tich-caption">Registered suppliers</p>
            <p class="tich-stat__value">{{ number_format($stats['total']) }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Payable-eligible</p>
            <p class="tich-stat__value">{{ number_format($stats['selectable']) }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Awaiting compliance approval</p>
            <p class="tich-stat__value">{{ number_format($stats['pending']) }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Blacklisted</p>
            <p class="tich-stat__value">{{ number_format($stats['blacklisted']) }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">AP invoices</p>
            <p class="tich-stat__value">{{ number_format($stats['invoices']) }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Outstanding payable</p>
            <p class="tich-stat__value">KES {{ number_format($stats['outstanding'], 2) }}</p>
        </article>
    </div>

    <form method="get" class="tich-filter-bar tich-mt-6 tich-mb-4">
        <input type="search" name="search" value="{{ $search }}" class="tich-input" placeholder="Search name, code, email, phone, KRA PIN…">
        <select name="compliance_status" class="tich-input">
            <option value="">All compliance statuses</option>
            @foreach ($complianceStatuses as $option)
                <option value="{{ $option }}" @selected($complianceStatus === $option)>{{ ucfirst(str_replace('_', ' ', $option)) }}</option>
            @endforeach
        </select>
        <select name="supplier_category" class="tich-input">
            <option value="">All categories</option>
            @foreach ($categories as $option)
                <option value="{{ $option }}" @selected($category === $option)>{{ ucfirst($option) }}</option>
            @endforeach
        </select>
        <select name="risk_rating" class="tich-input">
            <option value="">All risk ratings</option>
            @foreach ($riskRatings as $option)
                <option value="{{ $option }}" @selected($riskRating === $option)>{{ ucfirst($option) }}</option>
            @endforeach
        </select>
        <select name="status" class="tich-input">
            <option value="">All statuses</option>
            <option value="active" @selected($status === 'active')>Payable-eligible</option>
            <option value="blacklisted" @selected($status === 'blacklisted')>Blacklisted</option>
            <option value="inactive" @selected($status === 'inactive')>Inactive</option>
        </select>
        <button type="submit" class="tich-btn tich-btn-secondary">Filter</button>
        @if ($search || $complianceStatus || $category || $riskRating || $status)
            <a href="{{ route('finance.suppliers.index') }}" class="tich-btn tich-btn-ghost">Clear</a>
        @endif
    </form>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Supplier</th>
                        <th>Category</th>
                        <th>Compliance</th>
                        <th>KRA tax</th>
                        <th>Risk</th>
                        <th>Outstanding</th>
                        <th>Invoices</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr>
                            <td>{{ $supplier->supplier_code }}</td>
                            <td>
                                <strong>{{ $supplier->supplier_name }}</strong>
                                <p class="tich-caption" style="margin:0;">
                                    {{ $supplier->email }}
                                    @if ($supplier->phone)
                                        · {{ $supplier->phone }}
                                    @endif
                                    @if ($supplier->kra_pin)
                                        · PIN {{ $supplier->kra_pin }}
                                    @endif
                                </p>
                            </td>
                            <td>{{ ucfirst($supplier->supplier_category ?? '-') }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ match ($supplier->compliance_status) {
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    'under_review' => 'warning',
                                    default => 'secondary',
                                } }}">
                                    {{ ucfirst(str_replace('_', ' ', $supplier->compliance_status ?? 'pending')) }}
                                </span>
                            </td>
                            <td>{{ ucfirst(str_replace('_', ' ', $supplier->tax_compliance_status ?? 'pending review')) }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ $supplier->risk_rating === 'low' ? 'success' : ($supplier->risk_rating === 'high' ? 'danger' : 'secondary') }}">
                                    {{ ucfirst($supplier->risk_rating ?? 'medium') }}
                                </span>
                            </td>
                            <td class="tich-col-num">KES {{ number_format((float) $supplier->outstanding_balance, 2) }}</td>
                            <td class="tich-col-num">{{ number_format((int) $supplier->payable_count) }}</td>
                            <td>
                                @if ($supplier->isBlacklisted())
                                    <span class="tich-badge tich-badge--danger">Blacklisted</span>
                                @elseif ($supplier->isActive())
                                    <span class="tich-badge tich-badge--success">Payable-eligible</span>
                                @else
                                    <span class="tich-badge tich-badge--secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                @if ($supplier->isActive())
                                    <a href="{{ route('finance.ap.create', ['supplier_id' => $supplier->id]) }}" class="tich-link">New invoice</a>
                                @else
                                    <span class="tich-caption">Not payable</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="tich-table-empty">No suppliers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="tich-mt-4">{{ $suppliers->links() }}</div>
@endsection

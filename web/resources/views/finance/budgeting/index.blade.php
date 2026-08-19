@extends('layouts.finance')

@section('title', 'Budgeting')

@section('finance-content')
    <x-page-toolbar title="Budgeting" meta="Annual, departmental and project budgets, budget requests, approvals and budget vs actual tracking">
        <x-slot:actions>
            <a href="{{ route('finance.budgeting.create', $department) }}" class="tich-btn tich-btn-primary">+ New budget</a>
            <a href="{{ route('finance.budgeting.requests.index', $department) }}" class="tich-btn tich-btn-secondary">Review budget requests</a>
        </x-slot:actions>
    </x-page-toolbar>

    <form method="get" class="tich-flex tich-mt-6 tich-mb-4" style="gap:0.5rem; flex-wrap:wrap;">
        <input type="search" name="search" value="{{ $search }}" class="tich-input" placeholder="Search budget code or name…">
        <button type="submit" class="tich-btn tich-btn-secondary">Search</button>
    </form>

    @if ($forwardedRequests->isNotEmpty())
        <div class="tich-card tich-table-panel tich-mt-8">
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Forwarded request</th>
                            <th>Department</th>
                            <th>Requested</th>
                            <th>Stage</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($forwardedRequests as $item)
                            <tr>
                                <td>
                                    <strong>{{ $item->request_code }}</strong>
                                    <p class="tich-caption">{{ $item->title }}</p>
                                </td>
                                <td>{{ $item->department?->dept_name }}</td>
                                <td>KES {{ number_format($item->requested_amount, 0) }}</td>
                                <td><span class="tich-badge">{{ str_replace('_', ' ', ucfirst($item->status)) }}</span></td>
                                <td>
                                    <a href="{{ route('finance.budgeting.requests.show', [$department, $item->id]) }}" class="tich-btn tich-btn-primary">Review</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="tich-table-empty">No forwarded budget requests.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="tich-card tich-table-panel tich-mt-8">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Budget</th>
                        <th>Period</th>
                        <th>Department</th>
                        <th>Allocated</th>
                        <th>Spent</th>
                        <th>Committed</th>
                        <th>Available</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($budgets as $budget)
                        <tr>
                            <td>
                                <a href="{{ route('finance.budgeting.show', [$department, $budget]) }}">
                                    <strong>{{ $budget->budget_name }}</strong>
                                </a>
                                <p class="tich-caption">{{ $budget->budget_code }}</p>
                            </td>
                            <td>
                                {{ $budget->period_start?->format('M Y') }} – {{ $budget->period_end?->format('M Y') }}
                                <p class="tich-caption">FY {{ $budget->fiscal_year }}</p>
                            </td>
                            <td>{{ $budget->department?->dept_name ?? 'Institution-wide' }}</td>
                            <td>KES {{ number_format((float) $budget->allocated_amount, 2) }}</td>
                            <td>KES {{ number_format((float) $budget->spent_amount, 2) }}</td>
                            <td>KES {{ number_format((float) $budget->committed_amount, 2) }}</td>
                            <td>KES {{ number_format($budget->availableAmount(), 2) }}</td>
                            <td>{{ ucfirst($budget->status) }}</td>
                            <td>
                                <a href="{{ route('finance.budgeting.show', [$department, $budget]) }}" class="tich-link">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="tich-table-empty">No budgets yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="tich-mt-4">{{ $budgets->links() }}</div>
@endsection

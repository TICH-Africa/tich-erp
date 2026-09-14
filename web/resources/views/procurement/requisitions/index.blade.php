@extends('layouts.procurement')

@section('title', 'Requisitions')

@section('procurement-content')
    <x-page-toolbar title="Procurement Requisitions" meta="Create, track, and approve purchase requisitions">
        <x-slot:actions>
            <a href="{{ route('procurement.requisitions.create') }}" class="tich-btn tich-btn-primary">+ New requisition</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--3 tich-mt-6">
        <article class="tich-card tich-stat">
            <p class="tich-caption">Total</p>
            <p class="tich-stat__value">{{ number_format($stats['total']) }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Pending approval</p>
            <p class="tich-stat__value">{{ number_format($stats['pending']) }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Completed</p>
            <p class="tich-stat__value">{{ number_format($stats['completed']) }}</p>
        </article>
    </div>

    <form method="get" class="tich-flex tich-mt-6 tich-mb-4" style="gap:0.5rem; flex-wrap:wrap;">
        <input type="search" name="search" value="{{ $search }}" class="tich-input" placeholder="Search requisition number, requester, or department…">
        <select name="status" class="tich-input">
            <option value="">All statuses</option>
            <option value="draft" @selected($status === 'draft')>Draft</option>
            <option value="submitted" @selected($status === 'submitted')>Submitted</option>
            <option value="hod_approved" @selected($status === 'hod_approved')>HOD Approved</option>
            <option value="finance_approved" @selected($status === 'finance_approved')>Finance Approved</option>
            <option value="ceo_approved" @selected($status === 'ceo_approved')>CEO Approved</option>
            <option value="completed" @selected($status === 'completed')>Completed</option>
            <option value="rejected" @selected($status === 'rejected')>Rejected</option>
            <option value="cancelled" @selected($status === 'cancelled')>Cancelled</option>
        </select>
        <button type="submit" class="tich-btn tich-btn-secondary">Filter</button>
        @if($search || $status)
            <a href="{{ route('procurement.requisitions.index') }}" class="tich-btn tich-btn-ghost">Clear</a>
        @endif
    </form>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Requisition</th>
                        <th>Requested item</th>
                        <th>Department</th>
                        <th>Estimated cost</th>
                        <th>Status</th>
                        <th>HOD</th>
                        <th>Finance</th>
                        <th>CEO</th>
                        <th>Requested on</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requisitions as $requisition)
                        <tr>
                            <td>
                                <a href="{{ route('procurement.requisitions.show', $requisition) }}" class="tich-link">{{ $requisition->requisition_number }}</a>
                            </td>
                            <td>{{ $requisition->requested_item ?? '-' }}</td>
                            <td>{{ $requisition->department?->dept_name ?? '-' }}</td>
                            <td>KES {{ number_format((float) $requisition->estimated_cost, 2) }}</td>
                            <td>{{ $requisition->statusLabel() }}</td>
                            <td>{{ ucfirst($requisition->hod_approval_status) }}</td>
                            <td>{{ ucfirst($requisition->finance_approval_status) }}</td>
                            <td>{{ ucfirst($requisition->ceo_approval_status) }}</td>
                            <td>{{ $requisition->request_date?->format('d M Y') ?? '-' }}</td>
                            <td>
                                <a href="{{ route('procurement.requisitions.show', $requisition) }}" class="tich-link">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="tich-table-empty">No requisitions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="tich-mt-4">{{ $requisitions->links() }}</div>
@endsection

@extends('layouts.procurement')

@section('title', 'Requisitions')

@section('procurement-content')
    <x-page-toolbar title="Procurement Requisitions" meta="Create, track, and approve purchase requisitions">
        <x-slot:actions>
            <a href="{{ route('procurement.requisitions.create') }}" class="tich-btn tich-btn-primary">+ New requisition</a>
        </x-slot:actions>
    </x-page-toolbar>

    <form method="get" class="tich-filter-bar tich-mt-6 tich-mb-4">
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
        @if(request()->filled('per_page'))
            <input type="hidden" name="per_page" value="{{ request('per_page') }}">
        @endif
        <button type="submit" class="tich-btn tich-btn-secondary">Filter</button>
        @if($search || $status)
            <a href="{{ route('procurement.requisitions.index') }}" class="tich-btn tich-btn-ghost">Clear</a>
        @endif
    </form>

    <div class="tich-card tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th class="tich-col-num">#</th>
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
                            <td class="tich-col-num">{{ $requisitions->firstItem() + $loop->index }}</td>
                            <td>
                                <a href="{{ route('procurement.requisitions.show', $requisition) }}" class="tich-link">{{ $requisition->requisition_number }}</a>
                            </td>
                            <td>{{ $requisition->requested_item ?? '-' }}</td>
                            <td>{{ $requisition->department?->dept_name ?? '-' }}</td>
                            <td>KES {{ number_format((float) $requisition->estimated_cost, 2) }}</td>
                            <td>
                                <x-status-badge :status="$requisition->status" :label="$requisition->statusLabel()" />
                            </td>
                            <td>
                                <x-status-badge :status="$requisition->hod_approval_status" />
                            </td>
                            <td>
                                <x-status-badge :status="$requisition->finance_approval_status" />
                            </td>
                            <td>
                                <x-status-badge :status="$requisition->ceo_approval_status" />
                            </td>
                            <td>{{ $requisition->request_date?->format('d M Y') ?? '-' }}</td>
                            <td>
                                <a href="{{ route('procurement.requisitions.show', $requisition) }}" class="tich-link">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="tich-table-empty">No requisitions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('partials.pagination-footer', ['paginator' => $requisitions])
@endsection

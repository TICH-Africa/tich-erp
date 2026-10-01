@extends('layouts.ceo')

@section('title', 'Procurement requisitions')

@section('ceo-content')
    <x-page-toolbar title="Procurement requisitions" meta="All requisitions - approve or reject those awaiting CEO" />

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif

    <form method="get" class="tich-flex tich-mt-6 tich-mb-4" style="gap:0.5rem; flex-wrap:wrap;">
        <input type="search" name="search" value="{{ $search }}" class="tich-input" placeholder="Search number, item, budget…">
        <select name="status" class="tich-input" style="width:auto;">
            <option value="awaiting_ceo" @selected($status === 'awaiting_ceo')>Awaiting CEO</option>
            <option value="submitted" @selected($status === 'submitted')>Submitted</option>
            <option value="hod_approved" @selected($status === 'hod_approved')>HOD approved</option>
            <option value="finance_approved" @selected($status === 'finance_approved')>Finance approved</option>
            <option value="ceo_approved" @selected($status === 'ceo_approved')>CEO approved</option>
            <option value="rejected" @selected($status === 'rejected')>Rejected</option>
            <option value="all" @selected($status === 'all')>All statuses</option>
        </select>
        <button type="submit" class="tich-btn tich-btn-secondary">Filter</button>
    </form>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Requisition</th>
                        <th>Department</th>
                        <th>Amount</th>
                        <th>Requested</th>
                        <th>Stage</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->requisition_number }}</strong>
                                <p class="tich-caption">{{ $item->requested_item ?: \Illuminate\Support\Str::limit($item->justification, 60) }}</p>
                            </td>
                            <td>{{ $item->department?->dept_name ?? '-' }}</td>
                            <td>KES {{ number_format((float) $item->estimated_cost, 0) }}</td>
                            <td class="tich-caption">{{ $item->request_date?->format('d M Y') ?? '-' }}</td>
                            <td><x-status-badge :status="$item->status" /></td>
                            <td>
                                <a href="{{ route('ceo.procurement.show', $item) }}" class="tich-btn tich-btn-primary">Open</a>
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 6, 'title' => 'No requisitions in this queue', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($items instanceof \Illuminate\Contracts\Pagination\Paginator && $items->hasPages())
            <div class="tich-mt-4">{{ $items->links() }}</div>
        @endif
    </div>
@endsection

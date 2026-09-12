@extends('layouts.ceo')

@section('title', 'Budget authorizations')

@section('ceo-content')
    <x-page-toolbar title="Budget authorizations" meta="Department budgets awaiting executive approval" />

    <form method="get" class="tich-flex tich-mt-6 tich-mb-4" style="gap:0.5rem; flex-wrap:wrap;">
        <input type="search" name="search" value="{{ $search }}" class="tich-input" placeholder="Search request code or title…">
        <select name="status" class="tich-input" style="width:auto;">
            <option value="executive_review" @selected($status === 'executive_review')>Awaiting CEO</option>
            <option value="approved" @selected($status === 'approved')>Approved</option>
            <option value="rejected" @selected($status === 'rejected')>Rejected</option>
            <option value="disbursed" @selected($status === 'disbursed')>Disbursed</option>
            <option value="all" @selected($status === 'all')>All statuses</option>
        </select>
        <button type="submit" class="tich-btn tich-btn-secondary">Filter</button>
    </form>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Department</th>
                        <th>Requested</th>
                        <th>Verified</th>
                        <th>Stage</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->request_code }}</strong>
                                <p class="tich-caption">{{ $item->title }}</p>
                            </td>
                            <td>{{ $item->department?->dept_name }}</td>
                            <td>KES {{ number_format((float) $item->requested_amount, 0) }}</td>
                            <td>KES {{ number_format((float) ($item->verified_amount ?? 0), 0) }}</td>
                            <td><x-status-badge :status="$item->status" /></td>
                            <td>
                                <a href="{{ route('ceo.budgets.show', $item) }}" class="tich-btn tich-btn-primary">Open</a>
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 6, 'title' => 'No budget requests in this queue', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($requests instanceof \Illuminate\Contracts\Pagination\Paginator && $requests->hasPages())
            <div class="tich-mt-4">{{ $requests->links() }}</div>
        @endif
    </div>
@endsection

@extends('layouts.finance')

@section('title', 'Budget requests')

@section('finance-content')
    <x-page-toolbar title="Budget requests" meta="All budget requests from Administration">
        <x-slot:actions>
            <a href="{{ route('finance.budgeting.index') }}" class="tich-btn tich-btn-ghost">Back to budgets</a>
        </x-slot:actions>
    </x-page-toolbar>

    <form method="get" class="tich-flex tich-mt-6 tich-mb-4" style="gap:0.5rem; flex-wrap:wrap;">
        <input type="search" name="search" value="{{ $search }}" class="tich-input" placeholder="Search request code or title…">
        <select name="status" class="tich-input" style="width:auto;">
            <option value="">All statuses</option>
            <option value="submitted" {{ $status === 'submitted' ? 'selected' : '' }}>Submitted</option>
            <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Draft</option>
            <option value="finance_review" {{ $status === 'finance_review' ? 'selected' : '' }}>Finance review</option>
            <option value="executive_review" {{ $status === 'executive_review' ? 'selected' : '' }}>Executive review</option>
            <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Approved</option>
            <option value="disbursed" {{ $status === 'disbursed' ? 'selected' : '' }}>Disbursed</option>
            <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Rejected</option>
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
                        <th>Type</th>
                        <th>Requested</th>
                        <th>Verified</th>
                        <th>Approved</th>
                        <th>Stage</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $item)
                        @php $annual = $item->annualQuartersPayload(); @endphp
                        <tr>
                            <td>
                                <strong>{{ $item->request_code }}</strong>
                                <p class="tich-caption">{{ $item->title }}</p>
                            </td>
                            <td>{{ $item->department?->dept_name }}</td>
                            <td class="tich-caption">{{ $item->budget_type ? ucfirst($item->budget_type) : '—' }}</td>
                            <td>
                                KES {{ number_format($item->requested_amount, 0) }}
                                @if ($annual)
                                    <p class="tich-caption">Inc {{ number_format((float) ($annual['income_grand_total'] ?? 0), 0) }} · Exp {{ number_format((float) ($annual['expenditure_grand_total'] ?? 0), 0) }}</p>
                                @endif
                            </td>
                            <td>KES {{ number_format($item->verified_amount ?? 0, 0) }}</td>
                            <td>KES {{ number_format($item->approved_amount ?? 0, 0) }}</td>
                            <td><span class="tich-badge">{{ match($item->status) {
                                'submitted' => 'Awaiting Finance Review',
                                'draft' => 'Draft',
                                'finance_review' => 'In Finance Review',
                                'executive_review' => 'Awaiting Executive/CEO Approval',
                                'approved' => 'Approved - Awaiting Disbursement',
                                'disbursed' => 'Disbursed',
                                'rejected' => 'Rejected',
                                default => str_replace('_', ' ', ucfirst($item->status)),
                            } }}</span></td>
                            <td>
                                <a href="{{ route('finance.budgeting.requests.show', [$item->id]) }}" class="tich-btn tich-btn-primary">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr>@include('partials.states.table-empty', ['colspan' => 8, 'title' => 'No budget requests found.', 'icon' => 'inbox'])</tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="tich-mt-4">{{ $requests->links() }}</div>
@endsection


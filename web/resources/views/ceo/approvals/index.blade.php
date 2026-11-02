@extends('layouts.ceo')

@section('title', 'Approval workflow')

@section('ceo-content')
    <x-page-toolbar title="Approval workflow" meta="Track department budgets through Administration, Finance, and Executive stages" />

    @error('workflow')
        <div class="tich-alert tich-alert--error tich-mt-4">{{ $message }}</div>
    @enderror

    <div class="tich-card tich-table-panel tich-mt-8">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Department</th>
                        <th>Submitted by</th>
                        <th>Amount</th>
                        <th>Stage</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($queue as $item)
                        @php
                            $submitter = $submitters[(int) $item->submitted_by] ?? null;
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $item->request_code }}</strong>
                                <p class="tich-caption">{{ $item->title }}</p>
                            </td>
                            <td>{{ $item->department?->dept_name }}</td>
                            <td>
                                @if ($submitter)
                                    <strong>{{ $submitter['name'] }}</strong>
                                    <p class="tich-caption">{{ $submitter['email'] ?? '-' }}</p>
                                @else
                                    <span class="tich-caption">-</span>
                                @endif
                            </td>
                            <td>
                                <p>Req: KES {{ number_format((float) $item->requested_amount, 0) }}</p>
                                @if ($item->verified_amount !== null)
                                    <p class="tich-caption">Verified: KES {{ number_format((float) $item->verified_amount, 0) }}</p>
                                @endif
                            </td>
                            <td>
                                <span class="tich-badge">{{ match($item->status) {
                                    'submitted' => 'Awaiting Administration',
                                    'draft' => 'Draft',
                                    'finance_review' => 'In Finance review',
                                    'executive_review' => 'Awaiting CEO',
                                    default => str_replace('_', ' ', $item->status),
                                } }}</span>
                            </td>
                            <td>
                                @if ($item->status === 'executive_review')
                                    <a href="{{ route('ceo.budgets.show', $item) }}" class="tich-btn tich-btn-primary">Authorize</a>
                                @else
                                    <a href="{{ route('ceo.approvals.show', $item) }}" class="tich-btn tich-btn-secondary">Open</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 6, 'title' => 'No items in the approval pipeline', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($queue instanceof \Illuminate\Contracts\Pagination\Paginator && $queue->hasPages())
            <div class="tich-mt-4">{{ $queue->links() }}</div>
        @endif
    </div>
@endsection

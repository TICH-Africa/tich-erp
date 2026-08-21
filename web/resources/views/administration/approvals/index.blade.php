@extends('layouts.administration')

@section('title', 'Approval workflow')

@section('administration-content')
    <x-page-toolbar title="Approval workflow" meta="Review department budget requests before forwarding to Finance, or return them to the sender for revision." />

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
                        <th>Actions</th>
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
                                    <p class="tich-caption">{{ $submitter['email'] ?? '—' }}</p>
                                @else
                                    <span class="tich-caption">—</span>
                                @endif
                            </td>
                            <td>
                                <p>Req: KES {{ number_format($item->requested_amount, 0) }}</p>
                                @if ($item->verified_amount !== null)
                                    <p class="tich-caption">Verified: KES {{ number_format($item->verified_amount, 0) }}</p>
                                @endif
                                @if ($item->approved_amount !== null)
                                    <p class="tich-caption">Approved: KES {{ number_format($item->approved_amount, 0) }}</p>
                                @endif
                            </td>
                            <td><span class="tich-badge">{{ match($item->status) {
                                'submitted' => 'Awaiting Administration review',
                                'draft' => 'Draft',
                                'returned' => 'Returned to sender',
                                'finance_review' => 'In Finance review',
                                'executive_review' => 'Awaiting Executive/CEO',
                                'approved' => 'Approved',
                                'disbursed' => 'Disbursed',
                                'rejected' => 'Rejected',
                                default => str_replace('_', ' ', ucfirst($item->status)),
                            } }}</span></td>
                            <td>
                                <div class="tich-flex-wrap" style="gap: 0.5rem; align-items: center;">
                                    <a href="{{ route('administration.approvals.show', $item) }}" class="tich-btn tich-btn-primary">Review</a>

                                    @if ($item->status === 'submitted' || $item->status === 'draft')
                                        <form method="POST" action="{{ route('administration.approvals.route-finance', $item) }}" onsubmit="return confirm('Forward this budget request to Finance?')">
                                            @csrf
                                            <button type="submit" class="tich-btn tich-btn-secondary">Forward to Finance</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 6, 'title' => 'No items in the approval queue', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($queue instanceof \Illuminate\Contracts\Pagination\Paginator && $queue->hasPages())
            <div class="tich-mt-4">{{ $queue->links() }}</div>
        @endif
    </div>

    <div class="tich-alert tich-alert--info tich-mt-6">
        <strong>Workflow:</strong> Department submits → Administration reviews (notes, return, or forward) → Finance verifies → Executive/CEO authorizes → disbursement.
    </div>
@endsection

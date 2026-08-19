@extends('layouts.administration')

@section('title', 'Approval workflow')

@section('administration-content')
    <x-page-toolbar title="Approval workflow" meta="Administration creates and forwards budgets. Finance reviews, divides into groups, and forwards to Executive/CEO for final authorization." />

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
                        <th>Amount</th>
                        <th>Stage</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($queue as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->request_code }}</strong>
                                <p class="tich-caption">{{ $item->title }}</p>
                            </td>
                            <td>{{ $item->department?->dept_name }}</td>
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
                                <div class="tich-flex-wrap" style="gap: 0.5rem; align-items: center;">
                                    @if ($item->status === 'submitted' || $item->status === 'draft')
                                        <form method="POST" action="{{ route('administration.approvals.route-finance', $item) }}" onsubmit="return confirm('Forward this budget request to Finance?')">
                                            @csrf
                                            <button type="submit" class="tich-btn tich-btn-primary">Forward to Finance</button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('administration.approvals.reject', $item) }}" onsubmit="return confirm('Reject this request? This cannot be undone.')">
                                        @csrf
                                        <button type="submit" class="tich-btn tich-btn-danger">Reject</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="tich-table-empty">No items in the approval queue.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($queue instanceof \Illuminate\Contracts\Pagination\Paginator && $queue->hasPages())
            <div class="tich-mt-4">{{ $queue->links() }}</div>
        @endif
    </div>

    <div class="tich-alert tich-alert--info tich-mt-6">
        <strong>Workflow:</strong> Administration creates and forwards budgets to Finance. Finance reviews budgets, divides amounts into groups (annual, quarterly, monthly, weekly), and forwards to Executive/CEO for final authorization. Approved budgets can be disbursed.
    </div>
@endsection

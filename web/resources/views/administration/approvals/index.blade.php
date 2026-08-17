@extends('layouts.administration')

@section('title', 'Approval workflow')

@section('administration-content')
    <x-page-toolbar title="Approval workflow" meta="Automated routing: Departments → Finance verification → Executive/CEO authorization" />

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
                            </td>
                            <td><span class="tich-badge">{{ str_replace('_', ' ', ucfirst($item->status)) }}</span></td>
                            <td>
                                <div class="tich-flex-wrap" style="gap: 0.5rem;">
                                    @if ($item->status === 'submitted')
                                        <form method="POST" action="{{ route('administration.approvals.route-finance', $item) }}">
                                            @csrf
                                            <button type="submit" class="tich-btn tich-btn-secondary">Route to Finance</button>
                                        </form>
                                    @endif

                                    @if ($item->status === 'finance_review')
                                        <form method="POST" action="{{ route('administration.approvals.finance-verify', $item) }}" class="tich-flex-wrap" style="gap: 0.35rem; align-items: center;">
                                            @csrf
                                            <input type="number" step="0.01" name="verified_amount" class="tich-input" style="width: 8rem;" value="{{ $item->requested_amount }}" required>
                                            <button type="submit" class="tich-btn tich-btn-primary">Verify</button>
                                        </form>
                                    @endif

                                    @if ($item->status === 'executive_review')
                                        <form method="POST" action="{{ route('administration.approvals.executive-authorize', $item) }}" class="tich-flex-wrap" style="gap: 0.35rem; align-items: center;">
                                            @csrf
                                            <input type="number" step="0.01" name="approved_amount" class="tich-input" style="width: 8rem;" value="{{ $item->verified_amount ?? $item->requested_amount }}" required>
                                            <button type="submit" class="tich-btn tich-btn-primary">Authorize</button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('administration.approvals.reject', $item) }}" onsubmit="return confirm('Reject this request?')">
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
@endsection

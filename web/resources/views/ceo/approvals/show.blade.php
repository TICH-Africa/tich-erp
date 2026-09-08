@extends('layouts.ceo')

@section('title', 'Review budget request')

@section('ceo-content')
    <x-page-toolbar title="Review budget request" meta="{{ $budgetRequest->request_code }} - {{ $budgetRequest->title }}">
        <x-slot:actions>
            <a href="{{ route('ceo.approvals.index') }}" class="tich-btn tich-btn-ghost">Back to pipeline</a>
        </x-slot:actions>
    </x-page-toolbar>

    @error('workflow')
        <div class="tich-alert tich-alert--error tich-mt-4">{{ $message }}</div>
    @enderror

    <div class="tich-card tich-mt-6">
        <h2 class="tich-h3">Request details</h2>
        <div class="tich-grid tich-grid--2 tich-mt-4" style="gap:1rem;">
            <div>
                <p class="tich-caption">Department</p>
                <p><strong>{{ $budgetRequest->department?->dept_name }}</strong></p>
            </div>
            <div>
                <p class="tich-caption">Status</p>
                <p><span class="tich-badge">{{ str_replace('_', ' ', $budgetRequest->status) }}</span></p>
            </div>
            <div>
                <p class="tich-caption">Requested amount</p>
                <p><strong>KES {{ number_format((float) $budgetRequest->requested_amount, 2) }}</strong></p>
            </div>
            <div>
                <p class="tich-caption">Submitted by</p>
                @if ($submitter)
                    <p><strong>{{ $submitter['name'] }}</strong></p>
                    <p class="tich-caption">{{ $submitter['email'] ?? '' }}</p>
                @else
                    <p class="tich-caption">Unknown</p>
                @endif
            </div>
        </div>
        @if ($budgetRequest->justification)
            <div class="tich-mt-4">
                <p class="tich-caption">Justification</p>
                <p>{{ $budgetRequest->justification }}</p>
            </div>
        @endif
    </div>

    @if ($budgetRequest->workflow_notes)
        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">Workflow notes</h2>
            <pre class="tich-pre tich-mt-4" style="background:#f8fafc; padding:1rem; border-radius:0.5rem; white-space:pre-wrap;">{{ $budgetRequest->workflow_notes }}</pre>
        </div>
    @endif

    @if ($canAuthorize)
        <div class="tich-alert tich-alert--info tich-mt-6">
            This request is awaiting executive authorization.
            <a href="{{ route('ceo.budgets.show', $budgetRequest) }}" class="tich-link">Open budget authorization</a>
        </div>
    @elseif ($canAct)
        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">Administration actions</h2>
            <form method="POST" action="{{ route('ceo.approvals.review', $budgetRequest) }}" class="tich-form-stack tich-mt-4">
                @csrf
                <div class="tich-form-group">
                    <label class="tich-label" for="review_notes">Review notes</label>
                    <textarea id="review_notes" name="notes" class="tich-input" rows="3" maxlength="2000" required>{{ old('notes') }}</textarea>
                </div>
                <button type="submit" class="tich-btn tich-btn-secondary">Save review notes</button>
            </form>

            <div class="tich-grid tich-grid--2 tich-mt-6" style="gap:1.5rem;">
                <form method="POST" action="{{ route('ceo.approvals.return', $budgetRequest) }}" class="tich-form-stack" onsubmit="return confirm('Return this request to the submitting department?')">
                    @csrf
                    <div class="tich-form-group">
                        <label class="tich-label" for="return_notes">Return reason</label>
                        <textarea id="return_notes" name="notes" class="tich-input" rows="3" maxlength="2000" required></textarea>
                    </div>
                    <button type="submit" class="tich-btn tich-btn-secondary">Send back</button>
                </form>

                <div class="tich-form-stack">
                    <form method="POST" action="{{ route('ceo.approvals.route-finance', $budgetRequest) }}" onsubmit="return confirm('Forward this budget request to Finance?')">
                        @csrf
                        <button type="submit" class="tich-btn tich-btn-primary">Forward to Finance</button>
                    </form>
                    <form method="POST" action="{{ route('ceo.approvals.reject', $budgetRequest) }}" class="tich-mt-4" onsubmit="return confirm('Reject this request permanently?')">
                        @csrf
                        <div class="tich-form-group">
                            <label class="tich-label" for="reject_notes">Reject notes</label>
                            <textarea id="reject_notes" name="notes" class="tich-input" rows="2" maxlength="1000"></textarea>
                        </div>
                        <button type="submit" class="tich-btn tich-btn-danger">Reject</button>
                    </form>
                </div>
            </div>
        </div>
    @else
        <div class="tich-alert tich-alert--info tich-mt-6">
            This request is currently in <strong>{{ str_replace('_', ' ', $budgetRequest->status) }}</strong>.
        </div>
    @endif
@endsection

@extends('layouts.administration')

@section('title', 'Review budget request')

@section('administration-content')
    @php
        $annual = $budgetRequest->annualQuartersPayload();
        $incomeGrand = (float) ($annual['income_grand_total'] ?? 0);
        $expGrand = (float) ($annual['expenditure_grand_total'] ?? $budgetRequest->requested_amount);
        $balance = $incomeGrand - $expGrand;
        $statusLabel = match ($budgetRequest->status) {
            'submitted' => 'Awaiting Administration review',
            'draft' => 'Draft',
            'returned' => 'Returned to sender',
            'finance_review' => 'In Finance review',
            'executive_review' => 'Awaiting Executive/CEO',
            'approved' => 'Approved',
            'disbursed' => 'Disbursed',
            'rejected' => 'Rejected',
            default => str_replace('_', ' ', ucfirst($budgetRequest->status)),
        };
    @endphp

    <x-page-toolbar title="Review budget request" meta="{{ $budgetRequest->request_code }} · {{ $budgetRequest->title }}">
        <x-slot:actions>
            <a href="{{ route('administration.approvals.index') }}" class="tich-btn tich-btn-ghost">Back to queue</a>
        </x-slot:actions>
    </x-page-toolbar>

    @error('workflow')
        <div class="tich-alert tich-alert--error tich-mt-4">{{ $message }}</div>
    @enderror

    <div class="budrev-summary tich-mt-6">
        <div class="budrev-summary__main">
            <p class="budrev-summary__code">{{ $budgetRequest->request_code }}</p>
            <h2 class="budrev-summary__title">{{ $budgetRequest->title }}</h2>
            <p class="budrev-summary__meta">
                <strong>{{ $budgetRequest->department?->dept_name }}</strong>
                <span aria-hidden="true">·</span>
                {{ $budgetRequest->department?->dept_code }}
                <span aria-hidden="true">·</span>
                {{ $budgetRequest->budget_type ? ucfirst($budgetRequest->budget_type) : 'Budget' }}
                @if ($budgetRequest->planningCycle)
                    <span aria-hidden="true">·</span>
                    {{ $budgetRequest->planningCycle->title ?? $budgetRequest->planningCycle->cycle_code }}
                    @if ($budgetRequest->planningCycle->fiscal_year)
                        (FY{{ $budgetRequest->planningCycle->fiscal_year }})
                    @endif
                @endif
            </p>
            <div class="budrev-summary__status">
                <span class="tich-badge">{{ $statusLabel }}</span>
                <span class="tich-caption">
                    Submitted {{ $budgetRequest->submitted_at?->format('d M Y · H:i') ?? '—' }}
                    @if ($submitter)
                        by {{ $submitter['name'] }}
                    @endif
                </span>
            </div>
        </div>
        <div class="budrev-summary__figures">
            @if ($annual)
                <div class="budrev-stat">
                    <span class="budrev-stat__label">Total income</span>
                    <span class="budrev-stat__value">KES {{ number_format($incomeGrand, 2) }}</span>
                </div>
                <div class="budrev-stat budrev-stat--exp">
                    <span class="budrev-stat__label">Total expenditure</span>
                    <span class="budrev-stat__value">KES {{ number_format($expGrand, 2) }}</span>
                </div>
                <div class="budrev-stat {{ $balance >= 0 ? 'budrev-stat--ok' : 'budrev-stat--warn' }}">
                    <span class="budrev-stat__label">{{ $balance >= 0 ? 'Surplus' : 'Shortfall' }}</span>
                    <span class="budrev-stat__value">KES {{ number_format(abs($balance), 2) }}</span>
                </div>
            @else
                <div class="budrev-stat budrev-stat--exp">
                    <span class="budrev-stat__label">Requested amount</span>
                    <span class="budrev-stat__value">KES {{ number_format((float) $budgetRequest->requested_amount, 2) }}</span>
                </div>
            @endif
        </div>
    </div>

    @if ($budgetRequest->justification)
        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">Justification</h2>
            <p class="budrev-justification tich-mt-3">{{ $budgetRequest->justification }}</p>
            @if ($submitter)
                <p class="tich-caption tich-mt-3">
                    Contact: {{ $submitter['name'] }}
                    @if (! empty($submitter['email']))
                        · {{ $submitter['email'] }}
                    @endif
                </p>
            @endif
        </div>
    @endif

    @include('partials.budget-request-breakdown', ['budgetRequest' => $budgetRequest, 'idPrefix' => 'admin-budrev'])

    @if ($budgetRequest->workflow_notes)
        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">Workflow notes</h2>
            <pre class="tich-pre tich-mt-4 budrev-notes">{{ $budgetRequest->workflow_notes }}</pre>
        </div>
    @endif

    @if ($canAct)
        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">Administration actions</h2>
            <p class="tich-caption tich-mt-1">Review the request first. You can save notes, return it to the department, approve and forward it to Finance and M&E, or reject it.</p>

            <form method="POST" action="{{ route('administration.approvals.review', $budgetRequest) }}" class="tich-form-stack tich-mt-4">
                @csrf
                <div class="tich-form-group">
                    <label class="tich-label" for="review_notes">Review notes</label>
                    <textarea id="review_notes" name="notes" class="tich-input" rows="3" maxlength="2000" required placeholder="Comments for the record (request stays with Administration)">{{ old('notes') }}</textarea>
                </div>
                <button type="submit" class="tich-btn tich-btn-secondary">Save review notes</button>
            </form>

            <hr class="tich-mt-6" style="border:none; border-top:1px solid var(--tich-border, #e2e8f0);">

            <div class="tich-grid tich-grid--2 tich-mt-6" style="gap:1.5rem; align-items:start;">
                <form method="POST" action="{{ route('administration.approvals.return', $budgetRequest) }}" class="tich-form-stack" onsubmit="return confirm('Return this request to the submitting department?')">
                    @csrf
                    <div class="tich-form-group">
                        <label class="tich-label" for="return_notes">Return to sender - reason <span class="tich-text--danger">*</span></label>
                        <textarea id="return_notes" name="notes" class="tich-input" rows="3" maxlength="2000" required placeholder="What should the department revise?"></textarea>
                    </div>
                    <button type="submit" class="tich-btn tich-btn-secondary">Send back to sender</button>
                </form>

                <div class="tich-form-stack">
                    <form method="POST" action="{{ route('administration.approvals.route-finance', $budgetRequest) }}" onsubmit="return confirm('Approve and forward this request to Finance and M&E?')">
                        @csrf
                        <p class="tich-caption">When the request looks correct, approve it. Finance verifies the budget; M&E reviews the linked technical plan.</p>
                        <button type="submit" class="tich-btn tich-btn-primary tich-mt-2">Approve &amp; forward to Finance &amp; M&E</button>
                    </form>

                    <form method="POST" action="{{ route('administration.approvals.reject', $budgetRequest) }}" class="tich-mt-4" onsubmit="return confirm('Reject this request permanently?')">
                        @csrf
                        <div class="tich-form-group">
                            <label class="tich-label" for="reject_notes">Reject - notes (optional)</label>
                            <textarea id="reject_notes" name="notes" class="tich-input" rows="2" maxlength="1000"></textarea>
                        </div>
                        <button type="submit" class="tich-btn tich-btn-danger">Reject</button>
                    </form>
                </div>
            </div>
        </div>
    @else
        <div class="tich-alert tich-alert--info tich-mt-6">
            This request is past Administration intake (status: <strong>{{ str_replace('_', ' ', $budgetRequest->status) }}</strong>). Further actions happen in Finance / Executive stages.
        </div>
    @endif
@endsection

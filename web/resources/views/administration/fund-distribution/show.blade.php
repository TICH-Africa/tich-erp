@extends('layouts.administration')

@section('title', 'Fund distribution · '.$budgetRequest->request_code)

@section('administration-content')
    @php
        $annual = $budgetRequest->annualQuartersPayload();
        $incomeGrand = (float) ($annual['income_grand_total'] ?? 0);
        $expGrand = (float) ($annual['expenditure_grand_total'] ?? $budgetRequest->requested_amount);
        $balance = $incomeGrand - $expGrand;
        $displayAmount = (float) ($budgetRequest->approved_amount ?? $budgetRequest->verified_amount ?? $budgetRequest->requested_amount ?? 0);
        $statusLabel = match ($budgetRequest->status) {
            'submitted' => 'Awaiting Administration review',
            'finance_review' => 'In Finance review',
            'executive_review' => 'Awaiting Executive/CEO',
            'approved' => 'Approved — waiting disbursement',
            'disbursed' => 'Disbursed',
            'rejected' => 'Rejected',
            default => str_replace('_', ' ', ucfirst($budgetRequest->status)),
        };
    @endphp

    <x-page-toolbar title="Fund distribution" meta="{{ $budgetRequest->request_code }} · {{ $budgetRequest->title }}">
        <x-slot:actions>
            <a href="{{ route('administration.fund-distribution.index') }}" class="tich-btn tich-btn-ghost">Back to fund distribution</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif

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
            @endif
            <div class="budrev-stat budrev-stat--exp">
                <span class="budrev-stat__label">
                    @if ($budgetRequest->approved_amount !== null)
                        Approved amount
                    @elseif ($budgetRequest->verified_amount !== null)
                        Verified amount
                    @else
                        Requested amount
                    @endif
                </span>
                <span class="budrev-stat__value">KES {{ number_format($displayAmount, 2) }}</span>
            </div>
        </div>
    </div>

    @if ($budgetRequest->justification)
        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">Justification</h2>
            <p class="budrev-justification tich-mt-3">{{ $budgetRequest->justification }}</p>
        </div>
    @endif

    @include('partials.budget-request-breakdown', ['budgetRequest' => $budgetRequest, 'idPrefix' => 'fund-budrev'])

    @if (is_array($budgetRequest->group_allocations) && $budgetRequest->group_allocations !== [])
        <div class="tich-card tich-table-panel tich-mt-6">
            <h2 class="tich-h3">Group allocations</h2>
            <div class="tich-table-wrap tich-mt-4">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Label</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($budgetRequest->group_allocations as $group)
                            <tr>
                                <td>{{ ucfirst($group['type'] ?? '—') }}</td>
                                <td>{{ $group['label'] ?? '—' }}</td>
                                <td>KES {{ number_format((float) ($group['amount'] ?? 0), 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($budgetRequest->workflow_notes)
        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">Workflow notes</h2>
            <pre class="tich-pre tich-mt-4 budrev-notes">{{ $budgetRequest->workflow_notes }}</pre>
        </div>
    @endif

    @if ($budgetRequest->status === 'approved')
        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">Disbursement</h2>
            <p class="tich-caption tich-mt-1">Confirm funds have been released against this approved budget.</p>
            <form method="POST" action="{{ route('administration.fund-distribution.budget.disburse', $budgetRequest->id) }}" class="tich-form-stack tich-mt-4" onsubmit="return confirm('Mark this budget request as disbursed?')">
                @csrf
                <div class="tich-form-group">
                    <label class="tich-label" for="notes">Notes (optional)</label>
                    <textarea id="notes" name="notes" class="tich-input" rows="2" maxlength="2000" placeholder="Receipt / payment reference"></textarea>
                </div>
                <button type="submit" class="tich-btn tich-btn-primary">Mark as disbursed</button>
            </form>
        </div>
    @elseif ($budgetRequest->status === 'disbursed')
        <div class="tich-alert tich-alert--success tich-mt-6">
            <strong>Disbursed.</strong>
            @if ($budgetRequest->disbursed_at)
                Recorded {{ $budgetRequest->disbursed_at->format('d M Y H:i') }}.
            @endif
            @if ($budgetRequest->receipt_number)
                Receipt: {{ $budgetRequest->receipt_number }}.
            @endif
        </div>
    @endif
@endsection

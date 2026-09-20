@extends('layouts.ceo')

@section('title', 'Authorize budget')

@section('ceo-content')
    <x-page-toolbar title="Authorize budget" meta="{{ $budgetRequest->request_code }} - {{ $budgetRequest->title }}">
        <x-slot:actions>
            <a href="{{ route('ceo.budgets.index') }}" class="tich-btn tich-btn-ghost">Back to queue</a>
        </x-slot:actions>
    </x-page-toolbar>

    @error('workflow')
        <div class="tich-alert tich-alert--error tich-mt-4">{{ $message }}</div>
    @enderror

    <div class="tich-card tich-mt-6">
        <h2 class="tich-h3">Request details</h2>
        <div class="tich-grid tich-grid--3 tich-mt-4" style="gap:1rem;">
            <div>
                <p class="tich-caption">Department</p>
                <p><strong>{{ $budgetRequest->department?->dept_name }}</strong></p>
            </div>
            <div>
                <p class="tich-caption">Status</p>
                <p><x-status-badge :status="$budgetRequest->status" /></p>
            </div>
            <div>
                <p class="tich-caption">Planning cycle</p>
                <p>{{ $budgetRequest->planningCycle?->title ?? '-' }}</p>
            </div>
            <div>
                <p class="tich-caption">Requested</p>
                <p><strong>KES {{ number_format((float) $budgetRequest->requested_amount, 2) }}</strong></p>
            </div>
            <div>
                <p class="tich-caption">Verified</p>
                <p><strong>KES {{ number_format((float) ($budgetRequest->verified_amount ?? 0), 2) }}</strong></p>
            </div>
            <div>
                <p class="tich-caption">Approved</p>
                <p><strong>KES {{ number_format((float) ($budgetRequest->approved_amount ?? 0), 2) }}</strong></p>
            </div>
        </div>

        @if ($budgetRequest->justification)
            <div class="tich-mt-4">
                <p class="tich-caption">Justification</p>
                <p class="budrev-justification">{{ $budgetRequest->justification }}</p>
            </div>
        @endif
    </div>

    @include('partials.budget-request-breakdown', ['budgetRequest' => $budgetRequest, 'idPrefix' => 'ceo-budrev'])

    @php
        $groups = is_array($budgetRequest->group_allocations) ? $budgetRequest->group_allocations : [];
    @endphp

    @if ($groups !== [])
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
                        @foreach ($groups as $group)
                            <tr>
                                <td>{{ ucfirst($group['type'] ?? '-') }}</td>
                                <td>{{ $group['label'] ?? '-' }}</td>
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

    @if ($canAuthorize)
        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">Executive approval</h2>
            <p class="tich-caption tich-mt-2">Confirm the approved amount and authorize disbursement readiness.</p>

            <form method="POST" action="{{ route('ceo.budgets.approve', $budgetRequest) }}" class="tich-form-grid tich-mt-6">
                @csrf
                <div class="tich-form-group">
                    <label class="tich-label" for="approved_amount">Approved amount (KES)</label>
                    <input
                        id="approved_amount"
                        type="number"
                        step="0.01"
                        min="0"
                        name="approved_amount"
                        class="tich-input"
                        value="{{ old('approved_amount', $budgetRequest->verified_amount ?? $budgetRequest->requested_amount) }}"
                        required
                    >
                </div>
                <div class="tich-form-group">
                    <label class="tich-label" for="notes">Notes</label>
                    <textarea id="notes" name="notes" class="tich-input" rows="3" placeholder="Optional notes…">{{ old('notes') }}</textarea>
                </div>
                <div class="tich-flex-wrap" style="gap:0.75rem;">
                    <button type="submit" class="tich-btn tich-btn-primary">Approve</button>
                    <button
                        type="submit"
                        formaction="{{ route('ceo.budgets.reject', $budgetRequest) }}"
                        class="tich-btn tich-btn-danger"
                        onclick="return confirm('Reject this budget request?')"
                    >Reject</button>
                </div>
            </form>
        </div>
    @elseif ($budgetRequest->status === 'approved')
        <div class="tich-alert tich-alert--success tich-mt-6">
            <strong>Approved.</strong> Funds can now be disbursed by Finance.
        </div>
    @elseif ($budgetRequest->status === 'rejected')
        <div class="tich-alert tich-alert--error tich-mt-6">
            <strong>Rejected.</strong> This budget request has been rejected.
        </div>
    @endif
@endsection

@extends('layouts.finance')

@section('title', 'Review budget request')

@section('finance-content')
    <x-page-toolbar title="Review budget request" meta="{{ $budgetRequest->request_code }} — {{ $budgetRequest->title }}">
        <x-slot:actions>
            <a href="{{ route('finance.budgeting.requests.index', $department) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-mt-6">
        <h2 class="tich-h3">Request details</h2>
        <div class="tich-grid tich-grid-2 tich-mt-4">
            <div>
                <p class="tich-caption">Department</p>
                <p class="tich-font-semibold">{{ $budgetRequest->department?->dept_name }}</p>
            </div>
            <div>
                <p class="tich-caption">Framework</p>
                <p class="tich-font-semibold">{{ strtoupper($budgetRequest->framework) }}</p>
            </div>
            <div>
                <p class="tich-caption">Requested amount</p>
                <p class="tich-font-semibold">KES {{ number_format($budgetRequest->requested_amount, 2) }}</p>
            </div>
            <div>
                <p class="tich-caption">Status</p>
                <p class="tich-font-semibold"><span class="tich-badge">{{ match($budgetRequest->status) {
                    'submitted' => 'Awaiting Finance Review',
                    'finance_review' => 'In Finance Review',
                    'executive_review' => 'Awaiting Executive/CEO Approval',
                    'approved' => 'Approved - Awaiting Disbursement',
                    'disbursed' => 'Disbursed',
                    'rejected' => 'Rejected',
                    'draft' => 'Draft',
                    default => str_replace('_', ' ', ucfirst($budgetRequest->status)),
                } }}</span></p>
            </div>
            <div>
                <p class="tich-caption">Submitted by</p>
                <p class="tich-font-semibold">{{ $budgetRequest->submitted_at?->format('Y-m-d H:i') ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="tich-caption">Planning cycle</p>
                <p class="tich-font-semibold">{{ $budgetRequest->planningCycle?->title ?? 'N/A' }}</p>
            </div>
        </div>

        @if ($budgetRequest->justification)
            <div class="tich-mt-4">
                <p class="tich-caption">Justification</p>
                <p>{{ $budgetRequest->justification }}</p>
            </div>
        @endif

        @if ($budgetRequest->standard_line_items)
            <div class="tich-mt-4">
                <p class="tich-caption">Standard line items</p>
                <pre class="tich-pre" style="background:#f8fafc; padding:1rem; border-radius:0.5rem; overflow:auto;">{{ json_encode($budgetRequest->standard_line_items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        @endif

        @if ($budgetRequest->cbe_details)
            <div class="tich-mt-4">
                <p class="tich-caption">CBE details</p>
                <pre class="tich-pre" style="background:#f8fafc; padding:1rem; border-radius:0.5rem; overflow:auto;">{{ json_encode($budgetRequest->cbe_details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        @endif

        @if ($budgetRequest->group_allocations)
            <div class="tich-mt-4">
                <p class="tich-caption">Group allocations</p>
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
                                <td>{{ ucfirst($group['type'] ?? 'N/A') }}</td>
                                <td>{{ $group['label'] ?? 'N/A' }}</td>
                                <td>KES {{ number_format($group['amount'] ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($budgetRequest->workflow_notes)
            <div class="tich-mt-4">
                <p class="tich-caption">Workflow notes</p>
                <pre class="tich-pre" style="background:#f8fafc; padding:1rem; border-radius:0.5rem; white-space:pre-wrap;">{{ $budgetRequest->workflow_notes }}</pre>
            </div>
        @endif
    </div>

    @if (in_array($budgetRequest->status, ['finance_review', 'submitted']) && auth()->user()->hasAnyRole(['Finance Manager', 'CEO', 'Super Admin']))
        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">Finance review</h2>
            <p class="tich-caption tich-mt-2">Divide the budget into groups (annual, quarterly, monthly, weekly) and set the allocated amount. Then approve and forward to Executive/CEO for final authorization, or reject.</p>

            <form method="POST" action="{{ route('finance.budgeting.requests.review', [$department, $budgetRequest->id]) }}" class="tich-form tich-mt-4">
                @csrf
                <div class="tich-form__row">
                    <label class="tich-form__label">Allocated amount (KES)</label>
                    <input type="number" step="0.01" min="0" name="allocated_amount" class="tich-form__input" value="{{ old('allocated_amount', $budgetRequest->requested_amount) }}" required>
                </div>

                <div class="tich-form__row">
                    <label class="tich-form__label">Budget groups</label>
                    <div id="group-allocations">
                        @php
                            $groups = old('group_allocations', $budgetRequest->group_allocations ?? [['type' => 'annual', 'label' => 'Annual', 'amount' => 0]]);
                        @endphp
                        @foreach ($groups as $index => $group)
                            <div class="tich-form__row tich-flex-wrap" style="gap:0.5rem; align-items:end;">
                                <select name="group_allocations[{{ $index }}][type]" class="tich-form__input" style="width:auto;">
                                    <option value="annual" {{ ($group['type'] ?? '') === 'annual' ? 'selected' : '' }}>Annual</option>
                                    <option value="quarterly" {{ ($group['type'] ?? '') === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                    <option value="monthly" {{ ($group['type'] ?? '') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                    <option value="weekly" {{ ($group['type'] ?? '') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                </select>
                                <input type="text" name="group_allocations[{{ $index }}][label]" class="tich-form__input" style="width:auto;" placeholder="Label" value="{{ $group['label'] ?? '' }}" required>
                                <input type="number" step="0.01" min="0" name="group_allocations[{{ $index }}][amount]" class="tich-form__input" style="width:auto;" placeholder="Amount" value="{{ $group['amount'] ?? '' }}" required>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="tich-btn tich-btn-secondary tich-mt-2" id="add-group">+ Add group</button>
                </div>

                <div class="tich-form__row">
                    <label class="tich-form__label">Notes</label>
                    <textarea name="notes" class="tich-form__input" rows="3" placeholder="Optional notes...">{{ old('notes') }}</textarea>
                </div>

                <div class="tich-flex-wrap" style="gap:0.5rem;">
                    <button type="submit" class="tich-btn tich-btn-primary">Approve and forward to CEO</button>
                    <button type="submit" formaction="{{ route('finance.budgeting.requests.reject', [$department, $budgetRequest->id]) }}" formmethod="POST" class="tich-btn tich-btn-danger" onclick="return confirm('Reject this budget request?')">Reject</button>
                </div>
            </form>
        </div>
    @endif

    @if ($budgetRequest->status === 'executive_review' && auth()->user()->hasAnyRole(['CEO', 'Super Admin']))
        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">Executive/CEO approval</h2>
            <p class="tich-caption tich-mt-2">Review the verified budget and finalize the approved amount.</p>

            <form method="POST" action="{{ route('finance.budgeting.requests.ceo-approve', [$department, $budgetRequest->id]) }}" class="tich-form tich-mt-4">
                @csrf
                <div class="tich-form__row">
                    <label class="tich-form__label">Approved amount (KES)</label>
                    <input type="number" step="0.01" min="0" name="approved_amount" class="tich-form__input" value="{{ old('approved_amount', $budgetRequest->verified_amount ?? $budgetRequest->requested_amount) }}" required>
                </div>

                <div class="tich-form__row">
                    <label class="tich-form__label">Notes</label>
                    <textarea name="notes" class="tich-form__input" rows="3" placeholder="Optional notes...">{{ old('notes') }}</textarea>
                </div>

                <div class="tich-flex-wrap" style="gap:0.5rem;">
                    <button type="submit" class="tich-btn tich-btn-primary">CEO Approve</button>
                    <button type="submit" formaction="{{ route('finance.budgeting.requests.reject', [$department, $budgetRequest->id]) }}" formmethod="POST" class="tich-btn tich-btn-danger" onclick="return confirm('Reject this budget request?')">Reject</button>
                </div>
            </form>
        </div>
    @endif

    @if ($budgetRequest->status === 'approved')
        <div class="tich-alert tich-alert--success tich-mt-6">
            <strong>Approved.</strong> This budget has been approved by the Executive/CEO and funds can now be disbursed.
            @if ($budgetRequest->approved_amount)
                <p>Approved amount: KES {{ number_format($budgetRequest->approved_amount, 2) }}</p>
            @endif
        </div>

        @if (auth()->user()->hasAnyRole(['Finance Manager', 'CEO', 'Super Admin']))
            <div class="tich-card tich-mt-6">
                <h2 class="tich-h3">Mark as disbursed</h2>
                <p class="tich-caption tich-mt-2">Confirm that funds have been disbursed to the department. Enter receipt details and disbursement date.</p>

                <form method="POST" action="{{ route('finance.budgeting.requests.disburse', [$department, $budgetRequest->id]) }}" class="tich-form-grid tich-mt-4">
                    @csrf
                    <div class="tich-form-row">
                        <label class="tich-label" for="receipt_number">Receipt number <span class="tich-text--danger">*</span></label>
                        <input type="text" id="receipt_number" name="receipt_number" class="tich-input" placeholder="e.g. RCPT-2026-001" value="{{ old('receipt_number') }}" required>
                    </div>
                    <div class="tich-form-row">
                        <label class="tich-label" for="disbursed_at">Disbursement date <span class="tich-text--danger">*</span></label>
                        <input type="text" id="disbursed_at" name="disbursed_at" class="tich-input" placeholder="dd/mm/yyyy" value="{{ old('disbursed_at', now()->format('d/m/Y')) }}" required>
                    </div>
                    <div class="tich-form-row">
                        <label class="tich-label" for="notes">Notes</label>
                        <textarea id="notes" name="notes" class="tich-input" rows="4" placeholder="Optional notes...">{{ old('notes') }}</textarea>
                    </div>
                    <div class="tich-form-row">
                        <button type="submit" class="tich-btn tich-btn-primary">Mark as disbursed</button>
                    </div>
                </form>
            </div>
        @endif
    @endif

    @if ($budgetRequest->status === 'disbursed')
        <div class="tich-alert tich-alert--success tich-mt-6">
            <strong>Disbursed.</strong> This budget has been disbursed.
            @if ($budgetRequest->disbursed_at)
                <p>Disbursed on: {{ $budgetRequest->disbursed_at->format('d M Y H:i') }}</p>
            @endif
            @if ($budgetRequest->receipt_number)
                <p>Receipt number: {{ $budgetRequest->receipt_number }}</p>
            @endif
        </div>
    @endif

    @if ($budgetRequest->status === 'rejected')
        <div class="tich-alert tich-alert--error tich-mt-6">
            <strong>Rejected.</strong> This budget request has been rejected.
        </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const addButton = document.getElementById('add-group');
            const container = document.getElementById('group-allocations');
            if (!addButton || !container) return;

            addButton.addEventListener('click', function () {
                const index = container.children.length;
                const row = document.createElement('div');
                row.className = 'tich-form__row tich-flex-wrap';
                row.style.gap = '0.5rem';
                row.style.alignItems = 'end';
                row.innerHTML = `
                    <select name="group_allocations[${index}][type]" class="tich-form__input" style="width:auto;">
                        <option value="annual">Annual</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="monthly">Monthly</option>
                        <option value="weekly">Weekly</option>
                    </select>
                    <input type="text" name="group_allocations[${index}][label]" class="tich-form__input" style="width:auto;" placeholder="Label" required>
                    <input type="number" step="0.01" min="0" name="group_allocations[${index}][amount]" class="tich-form__input" style="width:auto;" placeholder="Amount" required>
                `;
                container.appendChild(row);
            });
        });

        const formatDateInput = function (input) {
            input.addEventListener('blur', function () {
                let value = input.value.replace(/[^\d]/g, '');
                if (value.length === 8) {
                    input.value = value.slice(0, 2) + '/' + value.slice(2, 4) + '/' + value.slice(4, 8);
                }
            });
        };

        const disbursedAtInput = document.getElementById('disbursed_at');
        if (disbursedAtInput) {
            formatDateInput(disbursedAtInput);
        }
    </script>
@endsection

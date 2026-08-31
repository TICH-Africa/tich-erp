@extends('layouts.finance')

@section('title', 'Review budget request')

@section('finance-content')
    <x-page-toolbar title="Review budget request" meta="{{ $budgetRequest->request_code }} - {{ $budgetRequest->title }}">
        <x-slot:actions>
            <a href="{{ route('finance.budgeting.requests.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-mt-6">
        <h2 class="tich-h3">Request details</h2>
        <div class="tich-grid tich-grid--4 tich-mt-4">
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
            @php
                $lines = is_array($budgetRequest->standard_line_items) ? $budgetRequest->standard_line_items : [];
                $structured = $lines !== [] && isset($lines[0]) && is_array($lines[0]) && array_key_exists('unit_price', $lines[0]);
            @endphp

            <div class="tich-mt-4">
                <p class="tich-caption">Standard line items</p>

                @if ($structured)
                    <div class="tich-table-wrap">
                        <table class="tich-admin-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Description</th>
                                    <th>Price per item</th>
                                    <th>UoM</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lines as $line)
                                    <tr>
                                        <td>{{ $line['item'] ?? '-' }}</td>
                                        <td>{{ $line['quantity'] ?? '-' }}</td>
                                        <td class="tich-caption">{{ $line['description'] ?? '-' }}</td>
                                        <td>KES {{ number_format((float) ($line['unit_price'] ?? 0), 2) }}</td>
                                        <td class="tich-caption">{{ $line['unit_of_measure'] ?? '-' }}</td>
                                        <td><strong>KES {{ number_format((float) ($line['total'] ?? (($line['quantity'] ?? 0) * ($line['unit_price'] ?? 0))), 2) }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" style="text-align:right; font-weight:600;">Grand total</td>
                                    <td><strong>KES {{ number_format((float) $budgetRequest->requested_amount, 2) }}</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <pre class="tich-pre" style="background:#f8fafc; padding:1rem; border-radius:0.5rem; overflow:auto;">{{ json_encode($lines, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                @endif
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
    </div>

    @if (in_array($budgetRequest->status, ['finance_review', 'submitted']) && auth()->user()->hasAnyRole(['Finance Manager', 'Assistant Finance Manager', 'CEO', 'Super Admin']))
        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">Finance review</h2>
            <p class="tich-caption tich-mt-2">Divide the budget into groups (annual, quarterly, monthly, weekly) and set the allocated amount. Then approve and forward to Executive/CEO for final authorization, or reject.</p>

            <form method="POST" action="{{ route('finance.budgeting.requests.review', [$budgetRequest->id]) }}" class="tich-form-grid tich-mt-6">
                @csrf
                <div class="tich-form-row">
                    <label class="tich-label">Allocated amount (KES)</label>
                    <input type="number" step="0.01" min="0" name="allocated_amount" class="tich-input" value="{{ old('allocated_amount', $budgetRequest->requested_amount) }}" required>
                </div>

                <div class="tich-form-row">
                    <label class="tich-label">Budget groups</label>
                <div class="tich-mt-2">
                    <div class="tich-grid tich-grid--3" style="gap: 1rem; align-items: end;">
                        <div>
                            <label class="tich-caption">Group</label>
                            <select id="new-group-type" class="tich-input">
                                <option value="annual">Annual</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="monthly">Monthly</option>
                                <option value="weekly">Weekly</option>
                            </select>
                        </div>
                        <div>
                            <label class="tich-caption">Label</label>
                            <input type="text" id="new-group-label" class="tich-input" placeholder="Label">
                        </div>
                        <div>
                            <label class="tich-caption">Amount</label>
                            <input type="number" step="0.01" min="0" id="new-group-amount" class="tich-input" placeholder="0.00">
                        </div>
                    </div>
                    <div class="tich-flex-wrap tich-mt-2" style="gap:0.5rem;">
                        <button type="button" class="tich-btn tich-btn-primary" id="add-group">+ Add group</button>
                        <button type="button" class="tich-btn tich-btn-ghost" id="cancel-add-group">Cancel</button>
                    </div>
                </div>
                    <div class="tich-table-wrap tich-mt-4">
                        <table class="tich-admin-table" id="group-allocations-table">
                            <thead>
                                <tr>
                                    <th style="min-width:10rem;">Type</th>
                                    <th style="min-width:12rem;">Label</th>
                                    <th style="min-width:10rem;">Amount</th>
                                    <th style="width:3rem;"></th>
                                </tr>
                            </thead>
                                <tbody>
                                    @php
                                        $groups = old('group_allocations', $budgetRequest->group_allocations ?? [['type' => 'annual', 'label' => 'Annual', 'amount' => 0]]);
                                    @endphp
                                    @foreach ($groups as $index => $group)
                                        <tr class="budget-group-row">
                                            <td>
                                                <input type="hidden" name="group_allocations[{{ $index }}][type]" value="{{ $group['type'] ?? 'annual' }}">
                                                {{ ucfirst($group['type'] ?? 'annual') }}
                                            </td>
                                            <td>
                                                <input type="hidden" name="group_allocations[{{ $index }}][label]" value="{{ $group['label'] ?? '' }}">
                                                {{ $group['label'] ?? '' }}
                                            </td>
                                            <td>
                                                <input type="hidden" name="group_allocations[{{ $index }}][amount]" value="{{ $group['amount'] ?? 0 }}">
                                                KES {{ number_format((float) ($group['amount'] ?? 0), 2) }}
                                            </td>
                                            <td>
                                                <button type="button" class="tich-btn tich-btn-ghost js-remove-group" title="Remove group" aria-label="Remove group">&times;</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                        </table>
                    </div>
                </div>

                <div class="tich-form-row">
                    <label class="tich-label">Notes</label>
                    <textarea name="notes" class="tich-input" rows="3" placeholder="Optional notes...">{{ old('notes') }}</textarea>
                </div>

                <div class="tich-flex-wrap" style="gap:0.75rem; justify-content:flex-end; margin-top:1rem;">
                    <button type="submit" class="tich-btn tich-btn-primary">Approve and forward to CEO</button>
                    <button type="submit" formaction="{{ route('finance.budgeting.requests.reject', [$budgetRequest->id]) }}" formmethod="POST" class="tich-btn tich-btn-danger" onclick="return confirm('Reject this budget request?')">Reject</button>
                </div>
            </form>
        </div>
    @endif

    @if ($budgetRequest->status === 'executive_review' && auth()->user()->hasAnyRole(['CEO', 'Super Admin']))
        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">Executive/CEO approval</h2>
            <p class="tich-caption tich-mt-2">Review the verified budget and finalize the approved amount.</p>

            <form method="POST" action="{{ route('finance.budgeting.requests.ceo-approve', [$budgetRequest->id]) }}" class="tich-form-grid tich-mt-6">
                @csrf
                <div class="tich-form-row">
                    <label class="tich-label">Approved amount (KES)</label>
                    <input type="number" step="0.01" min="0" name="approved_amount" class="tich-input" value="{{ old('approved_amount', $budgetRequest->verified_amount ?? $budgetRequest->requested_amount) }}" required>
                </div>

                <div class="tich-form-row">
                    <label class="tich-label">Notes</label>
                    <textarea name="notes" class="tich-input" rows="3" placeholder="Optional notes...">{{ old('notes') }}</textarea>
                </div>

                <div class="tich-flex-wrap" style="gap:0.75rem; justify-content:flex-end; margin-top:1rem;">
                    <button type="submit" class="tich-btn tich-btn-primary">CEO Approve</button>
                    <button type="submit" formaction="{{ route('finance.budgeting.requests.reject', [$budgetRequest->id]) }}" formmethod="POST" class="tich-btn tich-btn-danger" onclick="return confirm('Reject this budget request?')">Reject</button>
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

        @if (auth()->user()->hasAnyRole(['Finance Manager', 'Assistant Finance Manager', 'CEO', 'Super Admin']))
            <div class="tich-card tich-mt-6">
                <h2 class="tich-h3">Mark as disbursed</h2>
                <p class="tich-caption tich-mt-2">Confirm that funds have been disbursed to the department. Enter receipt details and disbursement date.</p>

                <form method="POST" action="{{ route('finance.budgeting.requests.disburse', [$budgetRequest->id]) }}" class="tich-form-grid tich-mt-4">
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
            const cancelButton = document.getElementById('cancel-add-group');
            const tableBody = document.querySelector('#group-allocations-table tbody');
            const typeSelect = document.getElementById('new-group-type');
            const labelInput = document.getElementById('new-group-label');
            const amountInput = document.getElementById('new-group-amount');

            if (!addButton || !tableBody || !typeSelect || !labelInput || !amountInput) return;

            function reindexRows() {
                Array.from(tableBody.querySelectorAll('tr')).forEach(function (row, index) {
                    row.querySelectorAll('input').forEach(function (input) {
                        input.name = input.name.replace(/group_allocations\[\d+]/, 'group_allocations[' + index + ']');
                    });
                });
            }

            function clearAddForm() {
                typeSelect.value = 'annual';
                labelInput.value = '';
                amountInput.value = '';
            }

            if (cancelButton) {
                cancelButton.addEventListener('click', clearAddForm);
            }

            addButton.addEventListener('click', function () {
                const index = tableBody.querySelectorAll('tr').length;
                const row = document.createElement('tr');
                row.className = 'budget-group-row';
                row.innerHTML =
                    '<td>' +
                        '<input type="hidden" name="group_allocations[' + index + '][type]" value="' + typeSelect.value + '">' +
                        typeSelect.options[typeSelect.selectedIndex].text +
                    '</td>' +
                    '<td>' +
                        '<input type="hidden" name="group_allocations[' + index + '][label]" value="' + labelInput.value.replace(/"/g, '&quot;') + '">' +
                        (labelInput.value || '-') +
                    '</td>' +
                    '<td>' +
                        '<input type="hidden" name="group_allocations[' + index + '][amount]" value="' + parseFloat(amountInput.value || '0').toFixed(2) + '">' +
                        'KES ' + parseFloat(amountInput.value || '0').toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) +
                    '</td>' +
                    '<td><button type="button" class="tich-btn tich-btn-ghost js-remove-group" title="Remove group" aria-label="Remove group">&times;</button></td>';
                tableBody.appendChild(row);
                clearAddForm();
            });

            tableBody.addEventListener('click', function (event) {
                const removeButton = event.target.closest('.js-remove-group');
                if (!removeButton) return;
                const row = removeButton.closest('tr');
                if (!row) return;
                row.remove();
                reindexRows();
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

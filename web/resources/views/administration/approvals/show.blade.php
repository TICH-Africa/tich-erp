@extends('layouts.administration')

@section('title', 'Review budget request')

@section('administration-content')
    <x-page-toolbar title="Review budget request" meta="{{ $budgetRequest->request_code }} - {{ $budgetRequest->title }}">
        <x-slot:actions>
            <a href="{{ route('administration.approvals.index') }}" class="tich-btn tich-btn-ghost">Back to queue</a>
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
                <p><strong>{{ $budgetRequest->department?->dept_name }}</strong> <span class="tich-caption">({{ $budgetRequest->department?->dept_code }})</span></p>
            </div>
            <div>
                <p class="tich-caption">Status</p>
                <p><span class="tich-badge">{{ match($budgetRequest->status) {
                    'submitted' => 'Awaiting Administration review',
                    'draft' => 'Draft',
                    'returned' => 'Returned to sender',
                    'finance_review' => 'In Finance review',
                    'executive_review' => 'Awaiting Executive/CEO',
                    'approved' => 'Approved',
                    'disbursed' => 'Disbursed',
                    'rejected' => 'Rejected',
                    default => str_replace('_', ' ', ucfirst($budgetRequest->status)),
                } }}</span></p>
            </div>
            <div>
                <p class="tich-caption">Requested amount</p>
                <p><strong>KES {{ number_format((float) $budgetRequest->requested_amount, 2) }}</strong></p>
            </div>
            <div>
                <p class="tich-caption">Budget type</p>
                <p>{{ $budgetRequest->budget_type ? ucfirst($budgetRequest->budget_type) : '-' }}</p>
            </div>
            <div>
                <p class="tich-caption">Planning cycle</p>
                <p>{{ $budgetRequest->planningCycle?->cycle_code ?? '-' }} {{ $budgetRequest->planningCycle?->title ? '- '.$budgetRequest->planningCycle->title : '' }}</p>
            </div>
            <div>
                <p class="tich-caption">Submitted by</p>
                @if ($submitter)
                    <p><strong>{{ $submitter['name'] }}</strong></p>
                    <p class="tich-caption">{{ $submitter['email'] ?? 'No email on file' }}</p>
                @else
                    <p class="tich-caption">Unknown submitter</p>
                @endif
            </div>
            <div>
                <p class="tich-caption">Submitted at</p>
                <p>{{ $budgetRequest->submitted_at?->format('d M Y H:i') ?? '-' }}</p>
            </div>
        </div>

        @if ($budgetRequest->justification)
            <div class="tich-mt-4">
                <p class="tich-caption">Justification</p>
                <p>{{ $budgetRequest->justification }}</p>
            </div>
        @endif
    </div>

    @php
        $lines = is_array($budgetRequest->standard_line_items) ? $budgetRequest->standard_line_items : [];
        $structured = $lines !== [] && isset($lines[0]) && is_array($lines[0]) && array_key_exists('unit_price', $lines[0]);
    @endphp

    <div class="tich-card tich-table-panel tich-mt-6">
        <h2 class="tich-h3">Line items</h2>
        <div class="tich-table-wrap tich-mt-4">
            @if ($structured)
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
            @elseif ($lines !== [])
                <pre class="tich-pre" style="background:#f8fafc; padding:1rem; border-radius:0.5rem; overflow:auto;">{{ json_encode($lines, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            @else
                <p class="tich-caption">No line items were provided with this request.</p>
            @endif
        </div>
    </div>

    @if ($budgetRequest->workflow_notes)
        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">Workflow notes</h2>
            <pre class="tich-pre tich-mt-4" style="background:#f8fafc; padding:1rem; border-radius:0.5rem; white-space:pre-wrap;">{{ $budgetRequest->workflow_notes }}</pre>
        </div>
    @endif

    @if ($canAct)
        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">Administration actions</h2>
            <p class="tich-caption tich-mt-1">Review the request first. You can save notes, return it to the department, forward it to Finance, or reject it.</p>

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
                    <form method="POST" action="{{ route('administration.approvals.route-finance', $budgetRequest) }}" onsubmit="return confirm('Forward this budget request to Finance?')">
                        @csrf
                        <p class="tich-caption">When the request looks correct, forward it for Finance verification.</p>
                        <button type="submit" class="tich-btn tich-btn-primary tich-mt-2">Forward to Finance</button>
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

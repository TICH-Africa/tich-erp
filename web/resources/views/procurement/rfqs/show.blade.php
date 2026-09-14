@extends('layouts.procurement')

@section('title', 'RFQ ' . $rfq->rfq_number)

@section('procurement-content')
    <x-page-toolbar title="RFQ {{ $rfq->rfq_number }}" meta="{{ ucfirst($rfq->status ?? 'draft') }}">
        <x-slot:actions>
            @if(($rfq->status ?? 'draft') === 'draft')
                <a href="#invite" class="tich-btn tich-btn-primary">Invite suppliers</a>
                <form method="POST" action="{{ route('procurement.rfqs.publish', $rfq) }}" class="tich-inline-form">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-secondary">Publish</button>
                </form>
            @elseif(($rfq->status ?? 'draft') === 'published')
                <form method="POST" action="{{ route('procurement.rfqs.close', $rfq) }}" class="tich-inline-form" onsubmit="return confirm('Close this RFQ? Quotations will be locked for evaluation.');">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-warning">Close for evaluation</button>
                </form>
            @elseif(($rfq->status ?? 'draft') === 'closed')
                <a href="#award" class="tich-btn tich-btn-success">Process award</a>
            @elseif(($rfq->status ?? 'draft') === 'awarded')
                <span class="tich-badge tich-badge--success">Awarded</span>
            @endif
            <a href="{{ route('procurement.rfqs.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="tich-alert tich-alert--error tich-mt-4">
            <ul style="margin:0; padding-left:1.25rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="tich-grid tich-grid--4 tich-mt-6">
        <article class="tich-card tich-stat">
            <p class="tich-caption">Invited suppliers</p>
            <p class="tich-stat__value">{{ $rfq->suppliers?->count() ?? 0 }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Quotations received</p>
            <p class="tich-stat__value">{{ $rfq->quotations?->count() ?? 0 }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Submission deadline</p>
            <p class="tich-stat__value">{{ $rfq->submission_deadline?->format('d M Y') ?? '-' }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Approval</p>
            <p class="tich-stat__value">{{ ucfirst(str_replace('_', ' ', $rfq->approval_status ?? 'pending')) }}</p>
        </article>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-6" style="gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3" style="margin-top:0;">RFQ details</h2>
            <dl class="tich-dl">
                <dt>RFQ number</dt>
                <dd>{{ $rfq->rfq_number }}</dd>
                <dt>Status</dt>
                <dd>{{ ucfirst($rfq->status ?? 'draft') }}</dd>
                <dt>Published</dt>
                <dd>{{ $rfq->published_at?->format('d M Y H:i') ?? '-' }}</dd>
                <dt>Closed</dt>
                <dd>{{ $rfq->closed_at?->format('d M Y H:i') ?? '-' }}</dd>
                <dt>Quantity</dt>
                <dd>{{ $rfq->quantity }}</dd>
                <dt>Minimum suppliers</dt>
                <dd>{{ $rfq->minimum_suppliers ?? 3 }}</dd>
                <dt>Categories (minimum)</dt>
                <dd>{{ $rfq->minimum_categories ? implode(', ', $rfq->minimum_categories) : '-' }}</dd>
                <dt>Delivery timeline</dt>
                <dd>{{ $rfq->delivery_timeline ?? '-' }}</dd>
                <dt>Delivery location</dt>
                <dd>{{ $rfq->delivery_location ?? '-' }}</dd>
            </dl>
            <h2 class="tich-h3 tich-mt-6">Item description</h2>
            <p class="tich-text">{{ $rfq->item_description }}</p>
            @if($rfq->specifications)
                <h2 class="tich-h3 tich-mt-6">Specifications</h2>
                <p class="tich-text">{{ $rfq->specifications }}</p>
            @endif
        </article>

        @if($rfq->requisition)
            <article class="tich-card">
                <h2 class="tich-h3" style="margin-top:0;">Source requisition</h2>
                <dl class="tich-dl">
                    <dt>Requisition #</dt>
                    <dd>{{ $rfq->requisition->requisition_number }}</dd>
                    <dt>Estimated cost</dt>
                    <dd>KES {{ number_format((float) $rfq->requisition->estimated_cost, 2) }}</dd>
                    <dt>Request date</dt>
                    <dd>{{ $rfq->requisition->request_date?->format('d M Y') ?? '-' }}</dd>
                </dl>
            </article>
        @endif
    </div>

    @if(($rfq->status ?? 'draft') === 'draft' && $eligibleSuppliers)
        <article class="tich-card tich-mt-6" id="invite">
            <h2 class="tich-h3" style="margin-top:0;">Invite suppliers</h2>
            <p class="tich-caption">Invite at least {{ $rfq->minimum_suppliers ?? 3 }} compliant, non-blacklisted suppliers.</p>
            <form method="POST" action="{{ route('procurement.rfqs.invite-suppliers', $rfq) }}" class="tich-form-stack tich-mt-4">
                @csrf
                <div class="tich-form-group">
                    <label class="tich-label" for="supplier_ids">Select suppliers *</label>
                    <select id="supplier_ids" name="supplier_ids[]" class="tich-input" multiple required>
                        @forelse($eligibleSuppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->supplier_name }} — {{ $supplier->supplier_code }} — {{ ucfirst($supplier->supplier_category ?? '-') }}</option>
                        @empty
                            <option disabled>No eligible suppliers found.</option>
                        @endforelse
                    </select>
                </div>
                <button type="submit" class="tich-btn tich-btn-primary">Send invitations</button>
            </form>
        </article>
    @endif

    <article class="tich-card tich-mt-6">
        <h2 class="tich-h3" style="margin-top:0;">Invited suppliers</h2>
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th>Code</th>
                        <th>Invited</th>
                        <th>Status</th>
                        <th>Quotation</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rfq->suppliers ?? [] as $invitation)
                        <tr>
                            <td>{{ $invitation->supplier->supplier_name ?? '-' }}</td>
                            <td>{{ $invitation->supplier->supplier_code ?? '-' }}</td>
                            <td>{{ $invitation->invited_at?->format('d M Y') ?? '-' }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $invitation->invitation_status)) }}</td>
                            <td>{{ $invitation->supplier_id && ($rfq->quotations ?? collect())->firstWhere('supplier_id', $invitation->supplier_id) ? 'Submitted' : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="tich-table-empty">No suppliers invited yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>

    @if(($rfq->status ?? 'draft') === 'closed' || ($rfq->status ?? 'draft') === 'awarded')
        <article class="tich-card tich-mt-6">
            <h2 class="tich-h3" style="margin-top:0;">Submitted quotations</h2>
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Supplier</th>
                            <th>Unit price</th>
                            <th>Total price</th>
                            <th>Delivery period</th>
                            <th>Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rfq->quotations ?? [] as $quotation)
                            <tr>
                                <td>{{ $quotation->supplier->supplier_name ?? '-' }}</td>
                                <td>KES {{ number_format((float) $quotation->unit_price, 2) }}</td>
                                <td>KES {{ number_format((float) $quotation->total_price, 2) }}</td>
                                <td>{{ $quotation->delivery_period ?? '-' }}</td>
                                <td>{{ $quotation->submitted_at?->format('d M Y') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="tich-table-empty">No quotations submitted.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    @endif

    @isset($compiledScores)
        @if($compiledScores)
            <article class="tich-card tich-mt-6">
                <h2 class="tich-h3" style="margin-top:0;">Compiled evaluation scores</h2>
                <div class="tich-table-wrap">
                    <table class="tich-admin-table">
                        <thead>
                            <tr>
                                <th>Supplier</th>
                                <th>Price (40)</th>
                                <th>Technical (30)</th>
                                <th>Delivery (15)</th>
                                <th>Payment terms (10)</th>
                                <th>Performance (5)</th>
                                <th>Total (100)</th>
                                <th>Rank</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($compiledScores as $row)
                                <tr>
                                    <td>{{ $row['supplier_name'] ?? '-' }}</td>
                                    <td>{{ number_format((float) ($row['price_score'] ?? 0), 2) }}</td>
                                    <td>{{ number_format((float) ($row['technical_score'] ?? 0), 2) }}</td>
                                    <td>{{ number_format((float) ($row['delivery_score'] ?? 0), 2) }}</td>
                                    <td>{{ number_format((float) ($row['payment_terms_score'] ?? 0), 2) }}</td>
                                    <td>{{ number_format((float) ($row['performance_score'] ?? 0), 2) }}</td>
                                    <td><strong>{{ number_format((float) ($row['total_score'] ?? 0), 2) }}</strong></td>
                                    <td>{{ $row['rank'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </article>
        @endif
    @endisset

    @if(($rfq->status ?? 'draft') === 'closed' && !($rfq->award_decision === 'awarded'))
        <article class="tich-card tich-mt-6" id="award">
            <h2 class="tich-h3" style="margin-top:0;">Award recommendation</h2>
            <form method="POST" action="{{ route('procurement.rfqs.award', $rfq) }}" class="tich-form-stack">
                @csrf
                <div class="tich-form-group">
                    <label class="tich-label" for="supplier_id">Award to supplier *</label>
                    <select id="supplier_id" name="supplier_id" class="tich-input" required>
                        <option value="">Select a supplier…</option>
                        @forelse($rfq->suppliers ?? [] as $invitation)
                            <option value="{{ $invitation->supplier_id }}">{{ $invitation->supplier->supplier_name ?? $invitation->supplier_id }}</option>
                        @endforelse
                    </select>
                </div>
                <button type="submit" class="tich-btn tich-btn-success">Recommend award</button>
            </form>
        </article>

        <article class="tich-card tich-mt-6">
            <h2 class="tich-h3" style="margin-top:0;">Submit evaluation</h2>
            <form method="POST" action="{{ route('procurement.rfqs.submit-evaluation', $rfq) }}" class="tich-form-stack">
                @csrf
                <div class="tich-grid tich-grid--2" style="gap:1rem;">
                    <div class="tich-form-group" style="grid-column: 1 / -1;">
                        <label class="tich-label" for="supplier_id">Supplier *</label>
                        <select id="supplier_id" name="supplier_id" class="tich-input" required>
                            @forelse($rfq->suppliers ?? [] as $invitation)
                                <option value="{{ $invitation->supplier_id }}">{{ $invitation->supplier->supplier_name ?? $invitation->supplier_id }}</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label" for="technical_score">Technical score (0-30) *</label>
                        <input type="number" step="0.01" min="0" max="30" id="technical_score" name="technical_score" class="tich-input" required>
                    </div>
                    <div class="tich-form-group" style="grid-column: 1 / -1;">
                        <label class="tich-label" for="comments">Comments</label>
                        <textarea id="comments" name="comments" rows="2" class="tich-input"></textarea>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label" for="has_conflict_of_interest">Conflict of interest?</label>
                        <select id="has_conflict_of_interest" name="has_conflict_of_interest" class="tich-input">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label" for="conflict_of_interest_details">Conflict details</label>
                        <input type="text" id="conflict_of_interest_details" name="conflict_of_interest_details" class="tich-input">
                    </div>
                </div>
                <button type="submit" class="tich-btn tich-btn-primary">Submit evaluation</button>
            </form>
        </article>
    @endif

    @if($rfq->award_decision === 'award_recommended' && $rfq->awarded_supplier_id)
        <article class="tich-card tich-mt-6">
            <h2 class="tich-h3" style="margin-top:0;">Approve award recommendation</h2>
            <p class="tich-caption">Recommended supplier: {{ $rfq->awardedSupplier->supplier_name ?? '-' }} — KES {{ number_format((float) $rfq->awarded_amount, 2) }}</p>
            <form method="POST" action="{{ route('procurement.rfqs.approve-award', $rfq) }}" class="tich-form-stack">
                @csrf
                <button type="submit" class="tich-btn tich-btn-success">Approve award</button>
            </form>
            <form method="POST" action="{{ route('procurement.rfqs.reject-award', $rfq) }}" class="tich-form-stack tich-mt-2">
                @csrf
                <div class="tich-form-group" style="grid-column:1/-1;">
                    <label class="tich-label" for="notes">Rejection reason *</label>
                    <textarea id="notes" name="notes" rows="2" class="tich-input" required></textarea>
                </div>
                <button type="submit" class="tich-btn tich-btn-danger">Reject award</button>
            </form>
        </article>
    @endif
@endsection

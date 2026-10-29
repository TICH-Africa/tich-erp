@extends('layouts.procurement')

@section('title', $requisition->requisition_number)

@section('procurement-content')
    <x-page-toolbar title="Requisition {{ $requisition->requisition_number }}" meta="Review details, verify budget, and process approvals">
        <x-slot:actions>
            <a href="{{ route('procurement.requisitions.index') }}" class="tich-btn tich-btn-ghost">Back</a>
            @if($requisition->isDraft())
                <form method="POST" action="{{ route('procurement.requisitions.submit', $requisition) }}" class="tich-inline-form">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-primary">Submit for approval</button>
                </form>
            @endif
            @if(!$requisition->isRejected() && !$requisition->isCompleted() && !$requisition->isDraft())
                <form method="POST" action="{{ route('procurement.requisitions.cancel', $requisition) }}" class="tich-inline-form" onsubmit="return confirm('Cancel this requisition? This action cannot be undone.');">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-danger">Cancel</button>
                </form>
            @endif
        </x-slot:actions>
    </x-page-toolbar>

    @if ($errors->any())
        <div class="tich-alert tich-alert--error tich-mt-4">
            <ul style="margin:0; padding-left:1.25rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="tich-grid tich-grid--3 tich-mt-6">
        <article class="tich-card tich-stat">
            <p class="tich-caption">Status</p>
            <p class="tich-stat__value">{{ $requisition->statusLabel() }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Estimated cost</p>
            <p class="tich-stat__value">{{ $requisition->isPriced() ? 'KES ' . number_format((float) $requisition->estimated_cost, 2) : 'Awaiting prices' }}</p>
        </article>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-6" style="gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3" style="margin-top:0;">Requisition details</h2>
            <dl class="tich-dl">
                <dt>Requisition number</dt>
                <dd>{{ $requisition->requisition_number }}</dd>
                <dt>Requested item / service</dt>
                <dd>{{ $requisition->requested_item ?? '-' }}</dd>
                <dt>Requesting department</dt>
                <dd>{{ $requisition->department?->dept_name ?? '-' }}</dd>
                <dt>Requested by</dt>
                <dd>{{ $requisition->requester?->full_name ?? '-' }}</dd>
                <dt>Request date</dt>
                <dd>{{ $requisition->request_date?->format('d M Y') ?? '-' }}</dd>
                <dt>Budget code</dt>
                <dd>{{ $requisition->budget_code ?? '-' }}</dd>
                <dt>Budget line</dt>
                <dd>{{ $requisition->budget_line ?? '-' }}</dd>
                <dt>Unit cost</dt>
                <dd>{{ $requisition->estimated_unit_cost !== null ? 'KES ' . number_format((float) $requisition->estimated_unit_cost, 2) : 'Awaiting prices' }}</dd>
                <dt>Quantity</dt>
                <dd>{{ $requisition->quantity ?? '-' }}</dd>
                <dt>Estimated total</dt>
                <dd>{{ $requisition->isPriced() ? 'KES ' . number_format((float) $requisition->estimated_cost, 2) : 'Filled by Procurement' }}</dd>
                <dt>Justification</dt>
                <dd>{{ $requisition->justification }}</dd>
            </dl>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3" style="margin-top:0;">Approval</h2>
            <p class="tich-text">This requisition is currently at the <strong>{{ ucfirst($requisition->currentApprovalLevel()) }}</strong> approval stage.</p>

            @if($requisition->hod_approved_at)
                <p class="tich-caption">HOD approved on {{ $requisition->hod_approved_at->format('d M Y H:i') }}</p>
            @endif
            @if($requisition->finance_approved_at)
                <p class="tich-caption">Finance approved on {{ $requisition->finance_approved_at->format('d M Y H:i') }}</p>
            @endif
            @if($requisition->ceo_approved_at)
                <p class="tich-caption">CEO approved on {{ $requisition->ceo_approved_at->format('d M Y H:i') }}</p>
            @endif

            <div class="tich-mt-6">
                <h3 class="tich-h4">Budget verification</h3>
                <p class="tich-text {{ $budgetCheck['passed'] ? 'tich-text--success' : 'tich-text--danger' }}">
                    {{ $budgetCheck['message'] }}
                </p>
                @if($budgetCheck['budget'])
                    <p class="tich-caption">Available budget: KES {{ number_format((float) $budgetCheck['available'], 2) }}</p>
                @endif
            </div>
        </article>
    </div>

    @if(is_array($requisition->line_items) && count($requisition->line_items) > 0)
        @php $openToPrice = ($canPrice ?? false) && !$requisition->isRejected() && !$requisition->isCompleted(); @endphp
        <article class="tich-card tich-table-panel tich-mt-6">
            <h2 class="tich-h3" style="margin-top:0;">Line items</h2>
            @if($openToPrice)
                <p class="tich-caption">Enter the estimated prices. The items and quantities stay as the requesting department filed them.</p>
            @endif
            <div class="tich-table-wrap tich-mt-4">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Description</th>
                            <th>Unit price</th>
                            <th>Unit</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requisition->line_items as $index => $line)
                            <tr>
                                <td class="tich-col-num">{{ $index + 1 }}</td>
                                <td>{{ $line['item'] ?? '-' }}</td>
                                <td>{{ $line['quantity'] ?? '-' }}</td>
                                <td>{{ $line['description'] ?: '-' }}</td>
                                <td>
                                    @if($openToPrice)
                                        <input type="number" name="lines[{{ $index }}]" form="requisition-price-form" class="tich-input" min="0" step="0.01" required placeholder="0.00" value="{{ old('lines.'.$index, $line['unit_price']) }}">
                                    @elseif(is_numeric($line['unit_price'] ?? null))
                                        KES {{ number_format((float) $line['unit_price'], 2) }}
                                    @else
                                        <span class="tich-caption">Awaiting prices</span>
                                    @endif
                                </td>
                                <td>{{ $line['unit_of_measure'] ?: '-' }}</td>
                                <td>{{ is_numeric($line['total'] ?? null) ? 'KES ' . number_format((float) $line['total'], 2) : '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($openToPrice)
                <form method="POST" action="{{ route('procurement.requisitions.price', $requisition) }}" class="tich-inline-form tich-mt-4">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-primary">{{ $requisition->isPriced() ? 'Update prices' : 'Save prices' }}</button>
                </form>
            @endif
        </article>
    @endif

    @if($requisition->attachments && count($requisition->attachments) > 0)
        <article class="tich-card tich-mt-6">
            <h2 class="tich-h3" style="margin-top:0;">Attachments</h2>
            <ul class="tich-list">
                @foreach($requisition->attachments as $attachment)
                    <li>
                        <a href="{{ asset('storage/' . $attachment) }}" target="_blank" class="tich-link">{{ basename($attachment) }}</a>
                    </li>
                @endforeach
            </ul>
        </article>
    @endif

    @if($requisition->audit_trail && count($requisition->audit_trail) > 0)
        <article class="tich-card tich-mt-6">
            <h2 class="tich-h3" style="margin-top:0;">Audit trail</h2>
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Level</th>
                            <th>Performed by</th>
                            <th>Performed at</th>
                            <th>Comments</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requisition->audit_trail as $entry)
                            <tr>
                                <td>{{ ucfirst($entry['action'] ?? '-') }}</td>
                                <td>{{ isset($entry['level']) ? ucfirst($entry['level']) : '-' }}</td>
                                <td>{{ $entry['performed_by'] ?? '-' }}</td>
                                <td>{{ $entry['performed_at'] ?? '-' }}</td>
                                <td>{{ $entry['comments'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>
    @endif
@endsection

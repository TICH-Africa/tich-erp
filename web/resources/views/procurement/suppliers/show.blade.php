@extends('layouts.procurement')

@section('title', $supplier->supplier_name)

@section('procurement-content')
    <x-page-toolbar title="{{ $supplier->supplier_name }}" meta="{{ $supplier->supplier_code }}">
        <x-slot:actions>
            <a href="{{ route('procurement.suppliers.edit', $supplier) }}" class="tich-btn tich-btn-secondary">Edit</a>
            @if(!$supplier->isBlacklisted())
                <form method="POST" action="{{ route('procurement.suppliers.blacklist', $supplier) }}" class="tich-inline-form" onsubmit="return confirm('Blacklist this supplier? This will exclude them from future RFQs.');">
                    @csrf
                    <input type="hidden" name="reason" value="Marked as blacklisted from supplier registry.">
                    <button type="submit" class="tich-btn tich-btn-danger">Blacklist</button>
                </form>
            @else
                <form method="POST" action="{{ route('procurement.suppliers.remove-blacklist', $supplier) }}" class="tich-inline-form">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-success">Remove blacklist</button>
                </form>
            @endif
            <a href="{{ route('procurement.suppliers.index') }}" class="tich-btn tich-btn-ghost">Back</a>
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
            <p class="tich-caption">Performance score</p>
            <p class="tich-stat__value">{{ number_format((float) $supplier->performance_score, 2) }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Risk rating</p>
            <p class="tich-stat__value">{{ ucfirst($supplier->risk_rating ?? 'medium') }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Compliance</p>
            <p class="tich-stat__value">{{ ucfirst(str_replace('_', ' ', $supplier->compliance_status)) }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Status</p>
            <p class="tich-stat__value">
                @if($supplier->isBlacklisted())
                    Blacklisted
                @elseif($supplier->isActive())
                    Active
                @else
                    Inactive
                @endif
            </p>
        </article>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-6" style="gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3" style="margin-top:0;">Business details</h2>
            <dl class="tich-dl">
                <dt>Supplier code</dt>
                <dd>{{ $supplier->supplier_code }}</dd>
                <dt>Company name</dt>
                <dd>{{ $supplier->supplier_name }}</dd>
                <dt>Registration number</dt>
                <dd>{{ $supplier->registration_number ?? '-' }}</dd>
                <dt>Contact person</dt>
                <dd>{{ $supplier->contact_person ?? '-' }}</dd>
                <dt>Email</dt>
                <dd>{{ $supplier->email }}</dd>
                <dt>Phone</dt>
                <dd>{{ $supplier->phone }}</dd>
                <dt>Postal address</dt>
                <dd>{{ $supplier->postal_address ?? '-' }}</dd>
                <dt>Physical address</dt>
                <dd>{{ $supplier->physical_address ?? '-' }}</dd>
                <dt>KRA PIN</dt>
                <dd>{{ $supplier->kra_pin ?? '-' }}</dd>
                <dt>Category</dt>
                <dd>{{ ucfirst($supplier->supplier_category ?? '-') }}
                    @if($supplier->sub_category)
                        ({{ $supplier->sub_category }})
                    @endif
                </dd>
                <dt>Staff count</dt>
                <dd>{{ $supplier->staff_count ?? '-' }}</dd>
                <dt>Notes</dt>
                <dd>{{ $supplier->notes ?? '-' }}</dd>
            </dl>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3" style="margin-top:0;">Bank details</h2>
            <dl class="tich-dl">
                <dt>Bank</dt>
                <dd>{{ $supplier->bank_name ?? '-' }}</dd>
                <dt>Account name</dt>
                <dd>{{ $supplier->bank_account_name ?? '-' }}</dd>
                <dt>Account number</dt>
                <dd>{{ $supplier->bank_account_number ?? '-' }}</dd>
                <dt>Branch</dt>
                <dd>{{ $supplier->bank_branch ?? '-' }}</dd>
            </dl>

            <h2 class="tich-h3 tich-mt-6">Verification</h2>
            <form method="POST" action="{{ route('procurement.suppliers.verify', $supplier) }}" class="tich-form-stack tich-mt-4">
                @csrf
                <div class="tich-grid tich-grid--2" style="gap:1rem;">
                    <div class="tich-form-group">
                        <label class="tich-label" for="compliance_status">Compliance status</label>
                        <select id="compliance_status" name="compliance_status" class="tich-input">
                            <option value="pending" @selected($supplier->compliance_status === 'pending')>Pending</option>
                            <option value="approved" @selected($supplier->compliance_status === 'approved')>Approved</option>
                            <option value="under_review" @selected($supplier->compliance_status === 'under_review')>Under review</option>
                            <option value="rejected" @selected($supplier->compliance_status === 'rejected')>Rejected</option>
                        </select>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label" for="risk_rating">Risk rating</label>
                        <select id="risk_rating" name="risk_rating" class="tich-input">
                            <option value="low" @selected($supplier->risk_rating === 'low')>Low</option>
                            <option value="medium" @selected($supplier->risk_rating === 'medium')>Medium</option>
                            <option value="high" @selected($supplier->risk_rating === 'high')>High</option>
                        </select>
                    </div>
                </div>
                <div class="tich-grid tich-grid--2" style="gap:1rem;">
                    <div class="tich-form-group">
                        <label class="tich-label" for="supplier_category">Category</label>
                        <select id="supplier_category" name="supplier_category" class="tich-input">
                            <option value="">None</option>
                            <option value="goods" @selected($supplier->supplier_category === 'goods')>Goods</option>
                            <option value="services" @selected($supplier->supplier_category === 'services')>Services</option>
                            <option value="works" @selected($supplier->supplier_category === 'works')>Works</option>
                        </select>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label" for="sub_category">Sub-category</label>
                        <input type="text" id="sub_category" name="sub_category" class="tich-input" value="{{ old('sub_category', $supplier->sub_category ?? '') }}">
                    </div>
                </div>
                <button type="submit" class="tich-btn tich-btn-primary tich-mt-2">Save verification</button>
            </form>
        </article>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-6" style="gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3" style="margin-top:0;">Compliance documents</h2>
            <dl class="tich-dl">
                <dt>Compliance certificate</dt>
                <dd>{{ $supplier->compliance_doc_path ? '<a href="'.asset('storage/'.$supplier->compliance_doc_path).'" target="_blank">View</a>' : '-' }}</dd>
                <dt>PIN certificate</dt>
                <dd>{{ $supplier->pin_certificate_path ? '<a href="'.asset('storage/'.$supplier->pin_certificate_path).'" target="_blank">View</a>' : '-' }}</dd>
                <dt>CR12</dt>
                <dd>{{ $supplier->cr12_path ? '<a href="'.asset('storage/'.$supplier->cr12_path).'" target="_blank">View</a>' : '-' }}</dd>
                <dt>Audited financial statements</dt>
                <dd>{{ $supplier->audited_financial_statements_path ? '<a href="'.asset('storage/'.$supplier->audited_financial_statements_path).'" target="_blank">View</a>' : '-' }}</dd>
            </dl>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3" style="margin-top:0;">Performance history</h2>
            @if($supplier->past_contracts && count($supplier->past_contracts) > 0)
                <div class="tich-table-wrap">
                    <table class="tich-admin-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($supplier->past_contracts as $entry)
                                <tr>
                                    <td>{{ $entry['updated_at'] ?? '-' }}</td>
                                    <td>{{ $entry['score'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="tich-text">No performance evaluations yet.</p>
            @endif
        </article>
    </div>

    @if($supplier->rfqs->isNotEmpty())
        <article class="tich-card tich-mt-6">
            <h2 class="tich-h3" style="margin-top:0;">Awarded RFQs</h2>
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>RFQ #</th>
                            <th>Item</th>
                            <th>Award date</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($supplier->rfqs as $rfq)
                            <tr>
                                <td><a href="{{ route('procurement.rfqs.show', $rfq) }}" class="tich-link">{{ $rfq->rfq_number }}</a></td>
                                <td>{{ Str::limit($rfq->item_description, 80) }}</td>
                                <td>{{ $rfq->closed_at?->format('d M Y') ?? '-' }}</td>
                                <td>KES {{ number_format((float) $rfq->awarded_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="tich-table-empty">No awarded RFQs.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    @endif
@endsection

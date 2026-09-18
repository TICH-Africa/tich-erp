@extends('layouts.procurement')

@section('title', 'New RFQ')

@section('procurement-content')
    <x-page-toolbar title="New RFQ" meta="Create a request for quotation from eligible suppliers">
        <x-slot:actions>
            <a href="{{ route('procurement.rfqs.index') }}" class="tich-btn tich-btn-ghost">Back to RFQs</a>
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

    <div class="uf-form">
        <form method="POST" action="{{ route('procurement.rfqs.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">RFQ · Request for quotation</div>
                    <div class="uf-amount-bar__sum">Invite eligible suppliers</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Source Requisition</div>
                <div class="uf-section-body">
                    <div class="uf-field">
                        <label for="requisition_id">Requisition <span class="uf-req">*</span></label>
                        <select id="requisition_id" name="requisition_id" required>
                            <option value="">Select a requisition…</option>
                            @forelse($requisitions as $req)
                                <option value="{{ $req->id }}" @selected(old('requisition_id') == $req->id || (isset($requisition) && $requisition?->id === $req->id))>
                                    {{ $req->requisition_number }} — KES {{ number_format((float) $req->estimated_cost, 2) }} — {{ $req->request_date?->format('d M Y') ?? '-' }}
                                </option>
                            @empty
                                <option disabled>No approved requisitions available.</option>
                            @endforelse
                        </select>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Quotation Details</div>
                <div class="uf-section-body">
                    <div class="uf-field">
                        <label for="item_description">Item / service description <span class="uf-req">*</span></label>
                        <textarea id="item_description" name="item_description" rows="3" required>{{ old('item_description') }}</textarea>
                    </div>
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="quantity">Quantity <span class="uf-req">*</span></label>
                            <input type="number" step="0.0001" id="quantity" name="quantity" value="{{ old('quantity') }}" required>
                        </div>
                        <div class="uf-field">
                            <label for="submission_deadline">Submission deadline <span class="uf-req">*</span></label>
                            <input type="text" id="submission_deadline" name="submission_deadline" placeholder="dd/mm/yyyy" value="{{ old('submission_deadline') }}" required>
                        </div>
                        <div class="uf-field">
                            <label for="delivery_timeline">Delivery timeline</label>
                            <input type="text" id="delivery_timeline" name="delivery_timeline" value="{{ old('delivery_timeline') }}">
                        </div>
                        <div class="uf-field">
                            <label for="delivery_location">Delivery location</label>
                            <input type="text" id="delivery_location" name="delivery_location" value="{{ old('delivery_location') }}">
                        </div>
                    </div>
                    <div class="uf-field">
                        <label for="specifications">Specifications / technical requirements</label>
                        <textarea id="specifications" name="specifications" rows="3">{{ old('specifications') }}</textarea>
                    </div>
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="minimum_suppliers">Minimum suppliers to invite <span class="uf-req">*</span></label>
                            <input type="number" min="3" id="minimum_suppliers" name="minimum_suppliers" value="{{ old('minimum_suppliers', 3) }}" required>
                        </div>
                        <div class="uf-field">
                            <label for="minimum_categories">Minimum category <span class="uf-req">*</span></label>
                            <select id="minimum_categories" name="minimum_categories[]" multiple>
                                @foreach(['goods' => 'Goods', 'services' => 'Services', 'works' => 'Works'] as $val => $label)
                                    <option value="{{ $val }}" @selected(in_array($val, old('minimum_categories', [])))>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="uf-field">
                            <label for="preferred_categories">Preferred category</label>
                            <select id="preferred_categories" name="preferred_categories[]" multiple>
                                @foreach(['goods' => 'Goods', 'services' => 'Services', 'works' => 'Works'] as $val => $label)
                                    <option value="{{ $val }}" @selected(in_array($val, old('preferred_categories', [])))>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Create RFQ (draft)</button>
                        <a href="{{ route('procurement.rfqs.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
@endsection

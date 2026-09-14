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

    <form method="POST" action="{{ route('procurement.rfqs.store') }}" class="tich-card tich-mt-6">
        @csrf

        <h2 class="tich-h3">Source requisition</h2>
        <div class="tich-form-group">
            <label class="tich-label" for="requisition_id">Requisition *</label>
            <select id="requisition_id" name="requisition_id" class="tich-input" required>
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

        <h2 class="tich-h3 tich-mt-6">Quotation details</h2>
        <div class="tich-grid tich-grid--2" style="gap:1rem;">
            <div class="tich-form-group" style="grid-column: 1 / -1;">
                <label class="tich-label" for="item_description">Item / service description *</label>
                <textarea id="item_description" name="item_description" rows="3" class="tich-input" required>{{ old('item_description') }}</textarea>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="quantity">Quantity *</label>
                <input type="number" step="0.0001" id="quantity" name="quantity" class="tich-input" value="{{ old('quantity') }}" required>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="submission_deadline">Submission deadline *</label>
                <input type="text" id="submission_deadline" name="submission_deadline" class="tich-input" placeholder="dd/mm/yyyy" value="{{ old('submission_deadline') }}" required>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="delivery_timeline">Delivery timeline</label>
                <input type="text" id="delivery_timeline" name="delivery_timeline" class="tich-input" value="{{ old('delivery_timeline') }}">
            </div>
            <div class="tich-form-group" style="grid-column: 1 / -1;">
                <label class="tich-label" for="delivery_location">Delivery location</label>
                <input type="text" id="delivery_location" name="delivery_location" class="tich-input" value="{{ old('delivery_location') }}">
            </div>
            <div class="tich-form-group" style="grid-column: 1 / -1;">
                <label class="tich-label" for="specifications">Specifications / technical requirements</label>
                <textarea id="specifications" name="specifications" rows="3" class="tich-input">{{ old('specifications') }}</textarea>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="minimum_suppliers">Minimum suppliers to invite *</label>
                <input type="number" min="3" id="minimum_suppliers" name="minimum_suppliers" class="tich-input" value="{{ old('minimum_suppliers', 3) }}" required>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="minimum_categories">Minimum category *</label>
                <select id="minimum_categories" name="minimum_categories[]" class="tich-input" multiple>
                    @foreach(['goods' => 'Goods', 'services' => 'Services', 'works' => 'Works'] as $val => $label)
                        <option value="{{ $val }}" @selected(in_array($val, old('minimum_categories', [])))>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="preferred_categories">Preferred category</label>
                <select id="preferred_categories" name="preferred_categories[]" class="tich-input" multiple>
                    @foreach(['goods' => 'Goods', 'services' => 'Services', 'works' => 'Works'] as $val => $label)
                        <option value="{{ $val }}" @selected(in_array($val, old('preferred_categories', [])))>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="tich-mt-6">
            <button type="submit" class="tich-btn tich-btn-primary">Create RFQ (draft)</button>
        </div>
    </form>
@endsection

@extends('layouts.procurement')

@section('title', 'Raise stock issue')

@section('procurement-content')
    <x-page-toolbar title="Raise stock issue" meta="HOD approval required">
        <x-slot:actions>
            <a href="{{ route('procurement.stock-issues.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="uf-form">
        <form method="POST" action="{{ route('procurement.stock-issues.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">STK · Stock issue</div>
                    <div class="uf-amount-bar__sum">HOD approval required</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Item &amp; Requester</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="inventory_item_id">Item <span class="uf-req">*</span></label>
                            <select id="inventory_item_id" name="inventory_item_id" required>
                                <option value="">Select item</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}">{{ $item->item_code }} - {{ $item->item_name }} ({{ $item->current_stock }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="uf-field">
                            <label for="department_id">Department <span class="uf-req">*</span></label>
                            <select id="department_id" name="department_id" required>
                                <option value="">Select department</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->dept_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="uf-field">
                            <label for="requested_by">Requested by <span class="uf-req">*</span></label>
                            <select id="requested_by" name="requested_by" required>
                                <option value="">Select staff</option>
                                @foreach($staff as $s)
                                    <option value="{{ $s->id }}">{{ $s->first_name }} {{ $s->surname }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Quantity &amp; Cost</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="unit_cost">Unit cost <span class="uf-req">*</span></label>
                            <input type="number" id="unit_cost" name="unit_cost" step="0.01" min="0" required>
                        </div>
                        <div class="uf-field">
                            <label for="quantity">Quantity <span class="uf-req">*</span></label>
                            <input type="number" id="quantity" name="quantity" step="0.0001" min="0.0001" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Reason &amp; Submit</div>
                <div class="uf-section-body">
                    <div class="uf-field">
                        <label for="reason">Reason <span class="uf-req">*</span></label>
                        <textarea id="reason" name="reason" rows="3" required></textarea>
                    </div>
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Raise issue</button>
                        <a href="{{ route('procurement.stock-issues.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
@endsection

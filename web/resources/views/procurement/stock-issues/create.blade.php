@extends('layouts.procurement')

@section('title', 'Raise stock issue')

@section('procurement-content')
    <x-page-toolbar title="Raise stock issue" meta="HOD approval required">
        <x-slot:actions>
            <a href="{{ route('procurement.stock-issues.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <form method="POST" action="{{ route('procurement.stock-issues.store') }}" class="tich-card tich-mt-6">
        @csrf
        <div class="tich-grid tich-grid--2" style="gap:1rem;">
            <div class="tich-form-group">
                <label class="tich-label" for="inventory_item_id">Item *</label>
                <select id="inventory_item_id" name="inventory_item_id" class="tich-input" required>
                    <option value="">Select item</option>
                    @foreach($items as $item)
                        <option value="{{ $item->id }}">{{ $item->item_code }} - {{ $item->item_name }} ({{ $item->current_stock }})</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="department_id">Department *</label>
                <select id="department_id" name="department_id" class="tich-input" required>
                    <option value="">Select department</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->dept_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="requested_by">Requested by *</label>
                <select id="requested_by" name="requested_by" class="tich-input" required>
                    <option value="">Select staff</option>
                    @foreach($staff as $s)
                        <option value="{{ $s->id }}">{{ $s->first_name }} {{ $s->surname }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="unit_cost">Unit cost *</label>
                <input type="number" id="unit_cost" name="unit_cost" class="tich-input" step="0.01" min="0" required>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="quantity">Quantity *</label>
                <input type="number" id="quantity" name="quantity" class="tich-input" step="0.0001" min="0.0001" required>
            </div>
            <div class="tich-form-group" style="grid-column:1/-1;">
                <label class="tich-label" for="reason">Reason *</label>
                <textarea id="reason" name="reason" rows="3" class="tich-input" required></textarea>
            </div>
        </div>
        <div class="tich-mt-6">
            <button type="submit" class="tich-btn tich-btn-primary">Raise issue</button>
        </div>
    </form>
@endsection

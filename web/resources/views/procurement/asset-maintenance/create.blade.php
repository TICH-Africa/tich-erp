@extends('layouts.procurement')

@section('title', 'Schedule maintenance')

@section('procurement-content')
    <x-page-toolbar title="Schedule maintenance" meta="Estates/ICT maintenance team">
        <x-slot:actions>
            <a href="{{ route('procurement.asset-maintenance.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <form method="POST" action="{{ route('procurement.asset-maintenance.store') }}" class="tich-card tich-mt-6">
        @csrf
        <div class="tich-grid tich-grid--2" style="gap:1rem;">
            <div class="tich-form-group">
                <label class="tich-label" for="asset_id">Asset *</label>
                <select id="asset_id" name="asset_id" class="tich-input" required>
                    <option value="">Select asset</option>
                    @foreach($assets as $asset)
                        <option value="{{ $asset->id }}">{{ $asset->asset_number }} - {{ $asset->asset_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="maintenance_type">Type *</label>
                <select id="maintenance_type" name="maintenance_type" class="tich-input" required>
                    <option value="repair">Repair</option>
                    <option value="scheduled">Scheduled</option>
                    <option value="preventive">Preventive</option>
                    <option value="emergency">Emergency</option>
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="priority">Priority *</label>
                <select id="priority" name="priority" class="tich-input" required>
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                    <option value="emergency">Emergency</option>
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="scheduled_date">Scheduled date *</label>
                <input type="date" id="scheduled_date" name="scheduled_date" class="tich-input" required>
            </div>
            <div class="tich-form-group" style="grid-column:1/-1;">
                <label class="tich-label" for="fault_description">Fault description *</label>
                <textarea id="fault_description" name="fault_description" rows="3" class="tich-input" required></textarea>
            </div>
        </div>
        <div class="tich-mt-6">
            <button type="submit" class="tich-btn tich-btn-primary">Schedule</button>
        </div>
    </form>
@endsection

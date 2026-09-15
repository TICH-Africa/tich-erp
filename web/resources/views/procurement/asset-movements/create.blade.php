@extends('layouts.procurement')

@section('title', 'Request asset movement')

@section('procurement-content')
    <x-page-toolbar title="Request asset movement" meta="Custodian or HOD request">
        <x-slot:actions>
            <a href="{{ route('procurement.asset-movements.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <form method="POST" action="{{ route('procurement.asset-movements.store') }}" class="tich-card tich-mt-6">
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
                <label class="tich-label" for="movement_type">Movement type *</label>
                <select id="movement_type" name="movement_type" class="tich-input" required>
                    <option value="transfer">Transfer</option>
                    <option value="relocation">Relocation</option>
                    <option value="assignment">Assignment</option>
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="from_location">Current location</label>
                <input type="text" id="from_location" name="from_location" class="tich-input">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="to_location">Proposed location *</label>
                <input type="text" id="to_location" name="to_location" class="tich-input" required>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="movement_date">Movement date *</label>
                <input type="date" id="movement_date" name="movement_date" class="tich-input" required>
            </div>
            <div class="tich-form-group" style="grid-column:1/-1;">
                <label class="tich-label" for="reason">Reason *</label>
                <textarea id="reason" name="reason" rows="3" class="tich-input" required></textarea>
            </div>
        </div>
        <div class="tich-mt-6">
            <button type="submit" class="tich-btn tich-btn-primary">Submit request</button>
        </div>
    </form>
@endsection

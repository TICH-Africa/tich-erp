@extends('layouts.procurement')

@section('title', 'Request asset disposal')

@section('procurement-content')
    <x-page-toolbar title="Request asset disposal" meta="Finance/HOD approval required">
        <x-slot:actions>
            <a href="{{ route('procurement.asset-disposals.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <form method="POST" action="{{ route('procurement.asset-disposals.store') }}" class="tich-card tich-mt-6">
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
                <label class="tich-label" for="disposal_type">Type *</label>
                <select id="disposal_type" name="disposal_type" class="tich-input" required>
                    <option value="write_off">Write-off</option>
                    <option value="donation">Donation</option>
                    <option value="auction">Auction</option>
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="disposal_value">Disposed value</label>
                <input type="number" id="disposal_value" name="disposal_value" class="tich-input" step="0.01" min="0">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="disposal_date">Disposal date</label>
                <input type="date" id="disposal_date" name="disposal_date" class="tich-input">
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

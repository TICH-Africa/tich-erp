@extends('layouts.procurement')

@section('title', 'New audit verification')

@section('procurement-content')
    <x-page-toolbar title="New audit verification" meta="HOD verification of assets">
        <x-slot:actions>
            <a href="{{ route('procurement.asset-audits.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <form method="POST" action="{{ route('procurement.asset-audits.store') }}" class="tich-card tich-mt-6">
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
                <label class="tich-label" for="auditor_id">Auditor (HOD) *</label>
                <select id="auditor_id" name="auditor_id" class="tich-input" required>
                    <option value="">Select staff</option>
                    @foreach($staff as $s)
                        <option value="{{ $s->id }}">{{ $s->first_name }} {{ $s->surname }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="verification_status">Verification status *</label>
                <select id="verification_status" name="verification_status" class="tich-input" required>
                    <option value="verified">Verified</option>
                    <option value="missing">Missing</option>
                    <option value="damaged">Damaged</option>
                    <option value="transferred">Transferred</option>
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="condition">Condition *</label>
                <select id="condition" name="condition" class="tich-input" required>
                    <option value="excellent">Excellent</option>
                    <option value="good">Good</option>
                    <option value="fair">Fair</option>
                    <option value="poor">Poor</option>
                    <option value="damaged">Damaged</option>
                </select>
            </div>
            <div class="tich-form-group" style="grid-column:1/-1;">
                <label class="tich-label" for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3" class="tich-input"></textarea>
            </div>
        </div>
        <div class="tich-mt-6">
            <button type="submit" class="tich-btn tich-btn-primary">Submit verification</button>
        </div>
    </form>
@endsection

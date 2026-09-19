@extends('layouts.procurement')

@section('title', 'Request asset disposal')

@section('procurement-content')
    <x-page-toolbar title="Request asset disposal" meta="Finance/HOD approval required">
        <x-slot:actions>
            <a href="{{ route('procurement.asset-disposals.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="uf-form">
        <form method="POST" action="{{ route('procurement.asset-disposals.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">DSP · Asset disposal</div>
                    <div class="uf-amount-bar__sum">Finance/HOD approval required</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Asset &amp; Disposal</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="asset_id">Asset <span class="uf-req">*</span></label>
                            <select id="asset_id" name="asset_id" required>
                                <option value="">Select asset</option>
                                @foreach($assets as $asset)
                                    <option value="{{ $asset->id }}">{{ $asset->asset_number }} - {{ $asset->asset_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="uf-field">
                            <label for="disposal_type">Type <span class="uf-req">*</span></label>
                            <select id="disposal_type" name="disposal_type" required>
                                <option value="write_off">Write-off</option>
                                <option value="donation">Donation</option>
                                <option value="auction">Auction</option>
                            </select>
                        </div>
                        <div class="uf-field">
                            <label for="disposal_value">Disposed value</label>
                            <input type="number" id="disposal_value" name="disposal_value" step="0.01" min="0">
                        </div>
                        <div class="uf-field">
                            <label for="disposal_date">Disposal date</label>
                            <input type="date" id="disposal_date" name="disposal_date">
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
                        <button type="submit" class="uf-btn uf-btn-primary">Submit request</button>
                        <a href="{{ route('procurement.asset-disposals.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
@endsection

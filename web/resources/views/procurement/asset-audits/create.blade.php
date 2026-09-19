@extends('layouts.procurement')

@section('title', 'New audit verification')

@section('procurement-content')
    <x-page-toolbar title="New audit verification" meta="HOD verification of assets">
        <x-slot:actions>
            <a href="{{ route('procurement.asset-audits.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="uf-form">
        <form method="POST" action="{{ route('procurement.asset-audits.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">AUD · Asset verification</div>
                    <div class="uf-amount-bar__sum">HOD physical check</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Asset &amp; Auditor</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="asset_id">Asset <span class="uf-req">*</span></label>
                            <select
                                id="asset_id"
                                name="asset_id"
                                required
                                class="{{ $errors->has('asset_id') ? 'is-invalid' : '' }}"
                            >
                                <option value="">Select asset</option>
                                @foreach($assets as $asset)
                                    <option value="{{ $asset->id }}" @selected(old('asset_id') == $asset->id)>
                                        {{ $asset->asset_number }} — {{ $asset->asset_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('asset_id')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                            <span class="uf-hint">Only non-disposed assets are listed</span>
                        </div>
                        <div class="uf-field">
                            <label for="auditor_id">Auditor (HOD) <span class="uf-req">*</span></label>
                            <select
                                id="auditor_id"
                                name="auditor_id"
                                required
                                class="{{ $errors->has('auditor_id') ? 'is-invalid' : '' }}"
                            >
                                <option value="">Select staff</option>
                                @foreach($staff as $s)
                                    <option value="{{ $s->id }}" @selected(old('auditor_id') == $s->id)>
                                        {{ $s->first_name }} {{ $s->surname }}
                                    </option>
                                @endforeach
                            </select>
                            @error('auditor_id')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Verification Result</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="verification_status">Verification status <span class="uf-req">*</span></label>
                            <select
                                id="verification_status"
                                name="verification_status"
                                required
                                class="{{ $errors->has('verification_status') ? 'is-invalid' : '' }}"
                            >
                                @foreach(['verified' => 'Verified', 'missing' => 'Missing', 'damaged' => 'Damaged', 'transferred' => 'Transferred'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('verification_status', 'verified') === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('verification_status')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="condition">Condition <span class="uf-req">*</span></label>
                            <select
                                id="condition"
                                name="condition"
                                required
                                class="{{ $errors->has('condition') ? 'is-invalid' : '' }}"
                            >
                                @foreach(['excellent' => 'Excellent', 'good' => 'Good', 'fair' => 'Fair', 'poor' => 'Poor', 'damaged' => 'Damaged'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('condition', 'good') === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('condition')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Notes &amp; Submit</div>
                <div class="uf-section-body">
                    <div class="uf-field">
                        <label for="notes">Notes</label>
                        <textarea
                            id="notes"
                            name="notes"
                            placeholder="Optional observations from the physical check…"
                            class="{{ $errors->has('notes') ? 'is-invalid' : '' }}"
                        >{{ old('notes') }}</textarea>
                        @error('notes')
                            <span class="uf-error">{{ $message }}</span>
                        @enderror
                        <span class="uf-hint">Optional — location issues, serial mismatches, etc.</span>
                    </div>
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Submit verification</button>
                        <a href="{{ route('procurement.asset-audits.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
@endsection

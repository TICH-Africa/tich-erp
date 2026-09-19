@extends('layouts.monitoring-evaluation')

@section('title', 'Upload M&E Policy')

@section('monitoring-evaluation-content')
    <x-page-toolbar title="Upload Standard M&E Policy" meta="Start-of-year policy for HOD digital sign-off">
        <x-slot:actions>
            <a href="{{ route('monitoring_evaluation.policies.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="uf-form">
        <form method="POST" action="{{ route('monitoring_evaluation.policies.store') }}" enctype="multipart/form-data" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">M&amp;E · Policy upload</div>
                    <div class="uf-amount-bar__sum">Upload Standard M&amp;E Policy</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Policy details</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="fiscal_year">Fiscal year <span class="uf-req">*</span></label>
                            <input type="text" id="fiscal_year" name="fiscal_year" value="{{ old('fiscal_year', date('Y')) }}" required maxlength="20" class="{{ $errors->has('fiscal_year') ? 'is-invalid' : '' }}">
                            @error('fiscal_year')<span class="uf-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="uf-field">
                            <label for="version">Version</label>
                            <input type="text" id="version" name="version" value="{{ old('version') }}" maxlength="50" placeholder="e.g. 2026.1">
                        </div>
                    </div>
                    <div class="uf-field">
                        <label for="title">Title <span class="uf-req">*</span></label>
                        <input type="text" id="title" name="title" value="{{ old('title', 'Standard M&E Policy') }}" required maxlength="300" class="{{ $errors->has('title') ? 'is-invalid' : '' }}">
                        @error('title')<span class="uf-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="uf-field">
                        <label for="effective_date">Effective date</label>
                        <input type="date" id="effective_date" name="effective_date" value="{{ old('effective_date') }}">
                    </div>
                    <div class="uf-field">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3" maxlength="5000">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Upload &amp; submit</div>
                <div class="uf-section-body">
                    <div class="uf-field">
                        <label for="policy_file">Policy file (PDF/DOC) <span class="uf-req">*</span></label>
                        <input type="file" id="policy_file" name="policy_file" required accept=".pdf,.doc,.docx,.png,.jpg,.jpeg" class="{{ $errors->has('policy_file') ? 'is-invalid' : '' }}">
                        @error('policy_file')<span class="uf-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Upload as draft</button>
                        <a href="{{ route('monitoring_evaluation.policies.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
@endsection

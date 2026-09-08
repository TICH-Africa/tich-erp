@extends('layouts.monitoring-evaluation')

@section('title', 'Upload M&E Policy')

@section('monitoring-evaluation-content')
    <x-page-toolbar title="Upload Standard M&E Policy" meta="Start-of-year policy for HOD digital sign-off">
        <x-slot:actions>
            <a href="{{ route('monitoring_evaluation.policies.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <form method="POST" action="{{ route('monitoring_evaluation.policies.store') }}" enctype="multipart/form-data" class="tich-card tich-form-stack tich-mt-6">
        @csrf
        <div class="tich-grid tich-grid--2" style="gap:1rem;">
            <div class="tich-form-group">
                <label class="tich-label" for="fiscal_year">Fiscal year *</label>
                <input type="text" id="fiscal_year" name="fiscal_year" class="tich-input" value="{{ old('fiscal_year', date('Y')) }}" required maxlength="20">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="version">Version</label>
                <input type="text" id="version" name="version" class="tich-input" value="{{ old('version') }}" maxlength="50" placeholder="e.g. 2026.1">
            </div>
        </div>
        <div class="tich-form-group">
            <label class="tich-label" for="title">Title *</label>
            <input type="text" id="title" name="title" class="tich-input" value="{{ old('title', 'Standard M&E Policy') }}" required maxlength="300">
        </div>
        <div class="tich-form-group">
            <label class="tich-label" for="effective_date">Effective date</label>
            <input type="date" id="effective_date" name="effective_date" class="tich-input" value="{{ old('effective_date') }}">
        </div>
        <div class="tich-form-group">
            <label class="tich-label" for="description">Description</label>
            <textarea id="description" name="description" class="tich-input" rows="3" maxlength="5000">{{ old('description') }}</textarea>
        </div>
        <div class="tich-form-group">
            <label class="tich-label" for="policy_file">Policy file (PDF/DOC) *</label>
            <input type="file" id="policy_file" name="policy_file" class="tich-input" required accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
        </div>
        <button type="submit" class="tich-btn tich-btn-primary">Upload as draft</button>
    </form>
@endsection

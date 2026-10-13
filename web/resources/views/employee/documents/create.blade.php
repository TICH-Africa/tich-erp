@extends('layouts.employee')

@section('employee-content')
    @php
        $requestedDocTypes = $requestedDocTypes ?? [];
        $preselectDocumentType = old('document_type', $preselectDocumentType ?? null);
        $docTypeLabels = \App\Services\EmployeeProfileCompletenessService::DOCUMENT_TYPE_LABELS;
        $requestedDocLabels = collect($requestedDocTypes)
            ->map(fn ($type) => $docTypeLabels[$type] ?? $type)
            ->all();
    @endphp

    <x-page-toolbar title="Upload Document" meta="Add a new document to your profile">
    </x-page-toolbar>

    @if (($profileUpdatePrompt ?? null) && $requestedDocLabels !== [])
        <div class="tich-alert tich-alert--warning tich-mt-4" role="status">
            <strong>Documents requested.</strong>
            {{ $profileUpdatePrompt->requested_via_module === 'ict' ? 'ICT' : 'HR' }} has asked you to upload:
            <strong>{{ implode(', ', $requestedDocLabels) }}</strong>.
            @if ($profileUpdatePrompt->notes)
                <p class="tich-mt-2 tich-caption"><strong>Note:</strong> {{ $profileUpdatePrompt->notes }}</p>
            @endif
        </div>
    @endif

    <div class="uf-form">
        <form method="POST" action="{{ route('employee.documents.store') }}" enctype="multipart/form-data" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">DOC · Employee profile</div>
                    <div class="uf-amount-bar__sum">Upload document</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Document Details</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="document_type">Document Type <span class="uf-req">*</span></label>
                            <select id="document_type" name="document_type" required class="{{ $errors->has('document_type') ? 'is-invalid' : '' }}">
                                <option value="">Select type</option>
                                @foreach ($docTypeLabels as $type => $label)
                                    <option value="{{ $type }}" @selected($preselectDocumentType === $type)>
                                        {{ $label }}@if (in_array($type, $requestedDocTypes, true)) (requested)@endif
                                    </option>
                                @endforeach
                            </select>
                            @error('document_type')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="document_name">Document Name <span class="uf-req">*</span></label>
                            <input type="text" id="document_name" name="document_name" required class="{{ $errors->has('document_name') ? 'is-invalid' : '' }}" value="{{ old('document_name') }}">
                            @error('document_name')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="file">File <span class="uf-req">*</span></label>
                            <input type="file" id="file" name="file" required class="{{ $errors->has('file') ? 'is-invalid' : '' }}" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,application/pdf,image/*">
                            <p class="uf-help">PDF, Word, or image — max 10 MB.</p>
                            @error('file')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="issue_date">Issue Date</label>
                            <input type="date" id="issue_date" name="issue_date" value="{{ old('issue_date') }}">
                        </div>
                        <div class="uf-field">
                            <label for="expiry_date">Expiry Date</label>
                            <input type="date" id="expiry_date" name="expiry_date" value="{{ old('expiry_date') }}">
                        </div>
                    </div>
                    <div class="uf-field">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" rows="2">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Upload Document</button>
                        <a href="{{ route('employee.documents.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
@endsection

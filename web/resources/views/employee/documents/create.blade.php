@extends('layouts.employee')

@section('employee-content')
    <x-page-toolbar title="Upload Document" meta="Add a new document to your profile">
    </x-page-toolbar>

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
                                <option value="cv" @selected(old('document_type') === 'cv')>CV / Resume</option>
                                <option value="academic_certificate" @selected(old('document_type') === 'academic_certificate')>Academic Certificate</option>
                                <option value="professional_license" @selected(old('document_type') === 'professional_license')>Professional License</option>
                                <option value="kra_pin" @selected(old('document_type') === 'kra_pin')>KRA PIN</option>
                                <option value="nssf" @selected(old('document_type') === 'nssf')>NSSF</option>
                                <option value="sha" @selected(old('document_type') === 'sha')>SHA</option>
                                <option value="national_id" @selected(old('document_type') === 'national_id')>National ID</option>
                                <option value="good_conduct" @selected(old('document_type') === 'good_conduct')>Good Conduct</option>
                                <option value="passport_photo" @selected(old('document_type') === 'passport_photo')>Passport Photo</option>
                                <option value="bank_confirmation" @selected(old('document_type') === 'bank_confirmation')>Bank Confirmation</option>
                                <option value="training_certification" @selected(old('document_type') === 'training_certification')>Training Certification</option>
                                <option value="other" @selected(old('document_type') === 'other')>Other</option>
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
                            <input type="file" id="file" name="file" required class="{{ $errors->has('file') ? 'is-invalid' : '' }}">
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

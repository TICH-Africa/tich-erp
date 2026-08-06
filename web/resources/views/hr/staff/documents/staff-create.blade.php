@extends('layouts.hr')

@section('title', 'Upload Document')

@section('hr-content')
    <div class="tich-mb-8">
        <a href="{{ route('employee.dashboard') }}" class="tich-btn tich-btn-ghost">&larr; Back to dashboard</a>
    </div>

    <article class="tich-card">
        <h1 class="tich-h1 tich-mb-6">Upload Document</h1>

        <form method="POST" action="{{ route('staff.documents.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="tich-grid tich-grid--2 tich-mb-6">
                <div>
                    <label for="document_type" class="tich-label">Document Type *</label>
                    <select id="document_type" name="document_type" required class="tich-input">
                        <option value="">Select type</option>
                        <option value="cv" {{ old('document_type') == 'cv' ? 'selected' : '' }}>CV / Resume</option>
                        <option value="academic_certificate" {{ old('document_type') == 'academic_certificate' ? 'selected' : '' }}>Academic Certificate</option>
                        <option value="professional_license" {{ old('document_type') == 'professional_license' ? 'selected' : '' }}>Professional License</option>
                        <option value="kra_pin" {{ old('document_type') == 'kra_pin' ? 'selected' : '' }}>KRA PIN</option>
                        <option value="nssf" {{ old('document_type') == 'nssf' ? 'selected' : '' }}>NSSF</option>
                        <option value="sha" {{ old('document_type') == 'sha' ? 'selected' : '' }}>SHA</option>
                        <option value="national_id" {{ old('document_type') == 'national_id' ? 'selected' : '' }}>National ID</option>
                        <option value="good_conduct" {{ old('document_type') == 'good_conduct' ? 'selected' : '' }}>Good Conduct</option>
                        <option value="passport_photo" {{ old('document_type') == 'passport_photo' ? 'selected' : '' }}>Passport Photo</option>
                        <option value="bank_confirmation" {{ old('document_type') == 'bank_confirmation' ? 'selected' : '' }}>Bank Confirmation</option>
                        <option value="training_certification" {{ old('document_type') == 'training_certification' ? 'selected' : '' }}>Training Certification</option>
                        <option value="other" {{ old('document_type') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div>
                    <label for="document_name" class="tich-label">Document Name *</label>
                    <input type="text" id="document_name" name="document_name" value="{{ old('document_name') }}" required class="tich-input" placeholder="e.g. First Aid Certification">
                </div>
                <div>
                    <label for="file" class="tich-label">File *</label>
                    <input type="file" id="file" name="file" required class="tich-input">
                </div>
                <div>
                    <label for="issue_date" class="tich-label">Issue Date</label>
                    <input type="date" id="issue_date" name="issue_date" value="{{ old('issue_date') }}" class="tich-input">
                </div>
                <div>
                    <label for="expiry_date" class="tich-label">Expiry Date</label>
                    <input type="date" id="expiry_date" name="expiry_date" value="{{ old('expiry_date') }}" class="tich-input">
                </div>
                <div class="tich-grid--span-2">
                    <label for="notes" class="tich-label">Notes</label>
                    <textarea id="notes" name="notes" rows="2" class="tich-input" placeholder="e.g. Completed First Aid Training on 15 July 2026">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="tich-mt-6">
                <button type="submit" class="tich-btn tich-btn-primary">Upload Document</button>
                <a href="{{ route('employee.dashboard') }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </article>
@endsection
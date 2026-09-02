@extends('layouts.employee')

@section('employee-content')
    <x-page-toolbar title="Upload Document" meta="Add a new document to your profile">
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <form method="POST" action="{{ route('employee.documents.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="tich-grid tich-grid--2 tich-mb-6">
                    <div>
                        <label for="document_type" class="tich-label">Document Type *</label>
                        <select id="document_type" name="document_type" required class="tich-input">
                            <option value="">Select type</option>
                            <option value="cv">CV / Resume</option>
                            <option value="academic_certificate">Academic Certificate</option>
                            <option value="professional_license">Professional License</option>
                            <option value="kra_pin">KRA PIN</option>
                            <option value="nssf">NSSF</option>
                            <option value="sha">SHA</option>
                            <option value="national_id">National ID</option>
                            <option value="good_conduct">Good Conduct</option>
                            <option value="passport_photo">Passport Photo</option>
                            <option value="bank_confirmation">Bank Confirmation</option>
                            <option value="training_certification">Training Certification</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label for="document_name" class="tich-label">Document Name *</label>
                        <input type="text" id="document_name" name="document_name" required class="tich-input">
                    </div>
                    <div>
                        <label for="file" class="tich-label">File *</label>
                        <input type="file" id="file" name="file" required class="tich-input">
                    </div>
                    <div>
                        <label for="issue_date" class="tich-label">Issue Date</label>
                        <input type="date" id="issue_date" name="issue_date" class="tich-input">
                    </div>
                    <div>
                        <label for="expiry_date" class="tich-label">Expiry Date</label>
                        <input type="date" id="expiry_date" name="expiry_date" class="tich-input">
                    </div>
                    <div class="tich-grid--span-2">
                        <label for="notes" class="tich-label">Notes</label>
                        <textarea id="notes" name="notes" rows="2" class="tich-input"></textarea>
                    </div>
                </div>

                <div class="tich-mt-6">
                    <button type="submit" class="tich-btn tich-btn-primary">Upload Document</button>
                    <a href="{{ route('employee.documents.index') }}" class="tich-btn tich-btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection

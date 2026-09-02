@extends('layouts.employee')

@section('employee-content')
    <x-page-toolbar title="My Documents" meta="View and manage your submitted documents">
        <x-slot:actions>
            <button onclick="document.getElementById('upload-modal').style.display='block'" class="tich-btn tich-btn-primary">+ Upload Document</button>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Document Type</th>
                        <th>Document Name</th>
                        <th>File</th>
                        <th>Issue Date</th>
                        <th>Expiry Date</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($staffDocuments as $doc)
                        <tr>
                            <td class="tich-caption">{{ ucfirst(str_replace('_', ' ', $doc->document_type)) }}</td>
                            <td>
                                <strong>{{ $doc->document_name }}</strong>
                                @if ($doc->notes)
                                    <p class="tich-caption tich-mt-1">{{ $doc->notes }}</p>
                                @endif
                            </td>
                            <td class="tich-caption">{{ $doc->original_filename }}</td>
                            <td class="tich-caption">{{ $doc->issue_date?->format('Y-m-d') ?? '-' }}</td>
                            <td class="tich-caption">{{ $doc->expiry_date?->format('Y-m-d') ?? '-' }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ $doc->is_verified ? 'success' : 'warning' }}">
                                    {{ $doc->is_verified ? 'Verified' : 'Pending' }}
                                </span>
                            </td>
                            <td class="tich-caption">{{ $doc->created_at?->format('Y-m-d') }}</td>
                            <td>
                                <a href="{{ route('employee.documents.download', $doc) }}" class="tich-btn tich-btn-ghost tich-btn--sm" target="_blank">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="tich-table-empty">No documents uploaded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="upload-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 2rem; border-radius: var(--radius-md); max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto;">
            <div class="tich-flex tich-flex--between tich-flex--start" style="margin-bottom: 1.5rem;">
                <h2 class="tich-h2">Upload Document</h2>
                <button onclick="document.getElementById('upload-modal').style.display='none'" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>

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
                    <button type="button" onclick="document.getElementById('upload-modal').style.display='none'" class="tich-btn tich-btn-ghost">Cancel</button>
                </div>
            </form>
        </div>
    </div>
@endsection

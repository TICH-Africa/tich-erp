@extends('layouts.hr')

@section('title', 'View Document - ' . $document->document_name)

@section('hr-content')
    <x-page-toolbar title="View Document" meta="{{ $document->original_filename }} · {{ $document->document_type }}">
        <x-slot:actions>
            <a href="{{ route('hr.documents.show', $document->staff) }}" class="tich-btn tich-btn-ghost">← Back to Documents</a>
            <a href="{{ route('hr.staff.documents.download', [$document->staff, $document]) }}" class="tich-btn tich-btn-secondary" target="_blank">Download</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-mb-6">
        <div class="tich-grid tich-grid--3">
            <div>
                <label class="tich-label">Document Name</label>
                <p class="tich-text">{{ $document->document_name }}</p>
            </div>
            <div>
                <label class="tich-label">Type</label>
                <p class="tich-text">{{ ucfirst(str_replace('_', ' ', $document->document_type)) }}</p>
            </div>
            <div>
                <label class="tich-label">Status</label>
                <p class="tich-text">
                    <span class="tich-badge tich-badge--{{ $document->status === 'approved' ? 'success' : ($document->status === 'rejected' ? 'danger' : 'warning') }}">
                        {{ ucfirst($document->status ?? 'pending') }}
                    </span>
                </p>
            </div>
            <div>
                <label class="tich-label">Issue Date</label>
                <p class="tich-text">{{ $document->issue_date?->format('Y-m-d') ?? '-' }}</p>
            </div>
            <div>
                <label class="tich-label">Expiry Date</label>
                <p class="tich-text">{{ $document->expiry_date?->format('Y-m-d') ?? '-' }}</p>
            </div>
            <div>
                <label class="tich-label">Uploaded</label>
                <p class="tich-text">{{ $document->created_at?->format('Y-m-d H:i') }}</p>
            </div>
        </div>
        @if ($document->rejection_reason)
            <div class="tich-mt-4 tich-p-4" style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px;">
                <label class="tich-label" style="color: #b91c1c;">Rejection Reason</label>
                <p class="tich-text" style="color: #b91c1c;">{{ $document->rejection_reason }}</p>
            </div>
        @endif
    </div>

    <div class="tich-card tich-mb-6" style="padding: 0; overflow: hidden;">
        @if (strpos($document->mime_type, 'pdf') !== false)
            <iframe src="{{ $fileUrl }}" style="width: 100%; height: 80vh; border: none;" title="{{ $document->document_name }}"></iframe>
        @elseif (strpos($document->mime_type, 'image') !== false)
            <img src="{{ $fileUrl }}" alt="{{ $document->document_name }}" style="width: 100%; max-height: 80vh; object-fit: contain;">
        @else
            <div style="padding: 60px; text-align: center;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color: #9ca3af; margin-bottom: 12px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                <p class="tich-text" style="color: #6b7280;">Preview not available for this file type.</p>
                <a href="{{ route('hr.staff.documents.download', [$document->staff, $document]) }}" class="tich-btn tich-btn-primary tich-mt-4">Download File</a>
            </div>
        @endif
    </div>

    <div class="tich-flex tich-flex--gap tich-flex--wrap">
        @if ($document->status !== 'approved')
            <form method="POST" action="{{ route('hr.staff.documents.approve', [$document->staff, $document]) }}" style="display:inline;" onsubmit="return confirm('Approve this document?')">
                @csrf
                <button type="submit" class="tich-btn tich-btn-primary">Approve</button>
            </form>
        @endif
        @if ($document->status !== 'rejected')
            <button type="button" class="tich-btn tich-btn-danger" data-open-modal="reject-{{ $document->id }}">Reject</button>
        @endif
    </div>

    @if ($document->status !== 'rejected')
        <div class="tich-modal" id="reject-{{ $document->id }}" data-modal>
            <div class="tich-modal__backdrop"></div>
            <div class="tich-modal__content">
                <h3 class="tich-h3">Reject Document</h3>
                <p class="tich-text tich-mt-4">Are you sure you want to reject <strong>{{ $document->document_name }}</strong>?</p>
                <form method="POST" action="{{ route('hr.staff.documents.reject', [$document->staff, $document]) }}" class="tich-mt-4">
                    @csrf
                    <div class="tich-form-group">
                        <label for="rejection_reason_{{ $document->id }}" class="tich-label">Reason *</label>
                        <textarea id="rejection_reason_{{ $document->id }}" name="rejection_reason" rows="3" required class="tich-input" placeholder="Provide a reason for rejection..."></textarea>
                    </div>
                    <div class="tich-flex tich-gap-4 tich-mt-6">
                        <button type="submit" class="tich-btn tich-btn-danger">Reject</button>
                        <button type="button" class="tich-btn tich-btn-ghost" data-close-modal="reject-{{ $document->id }}">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
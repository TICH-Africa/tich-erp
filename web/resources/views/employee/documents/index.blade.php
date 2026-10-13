@extends('layouts.employee')

@section('employee-content')
    @php
        $requestedDocTypes = $requestedDocTypes ?? [];
        $preselectDocumentType = old('document_type', $preselectDocumentType ?? null);
        $docTypeLabels = \App\Services\EmployeeProfileCompletenessService::DOCUMENT_TYPE_LABELS;
        $requestedDocLabels = collect($requestedDocTypes)
            ->map(fn ($type) => $docTypeLabels[$type] ?? $type)
            ->all();
        $openUploadModal = $errors->any() || (($profileUpdatePrompt ?? null) && $requestedDocTypes !== []);
    @endphp

    <x-page-toolbar title="My Documents" meta="View and manage your submitted documents">
        <x-slot:actions>
            <button type="button" class="tich-btn tich-btn-primary" data-open-modal="upload-document-modal">+ Upload Document</button>
        </x-slot:actions>
    </x-page-toolbar>

    @if (($profileUpdatePrompt ?? null) && $requestedDocLabels !== [])
        <div class="tich-alert tich-alert--warning tich-mt-4" role="status">
            <strong>Documents requested.</strong>
            {{ $profileUpdatePrompt->requested_via_module === 'ict' ? 'ICT' : 'HR' }} has asked you to upload:
            <strong>{{ implode(', ', $requestedDocLabels) }}</strong>.
            Use <strong>Upload Document</strong> and pick the matching type
            @if (count($requestedDocTypes) > 1)
                (upload each requested type)
            @endif.
            @if ($profileUpdatePrompt->notes)
                <p class="tich-mt-2 tich-caption"><strong>Note:</strong> {{ $profileUpdatePrompt->notes }}</p>
            @endif
            <p class="tich-mt-2">
                <a href="{{ route('employee.documents.create', ['prompt' => $profileUpdatePrompt->token]) }}" class="tich-btn tich-btn-secondary tich-btn--sm">Open full upload form</a>
            </p>
        </div>
    @endif

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

    <div
        id="upload-document-modal"
        class="tich-modal{{ $openUploadModal ? ' is-open' : '' }}"
        aria-hidden="{{ $openUploadModal ? 'false' : 'true' }}"
        role="dialog"
        aria-modal="true"
        aria-labelledby="upload-document-modal-title"
    >
        <div class="tich-modal__backdrop" data-close-modal="upload-document-modal"></div>
        <div class="tich-modal__dialog">
            <header class="tich-modal__header">
                <h2 class="tich-h3" id="upload-document-modal-title">Upload Document</h2>
                <button type="button" class="tich-modal__close" data-close-modal="upload-document-modal" aria-label="Close">&times;</button>
            </header>

            <form method="POST" action="{{ route('employee.documents.store') }}" enctype="multipart/form-data" class="tich-modal__body" data-uf="skip">
                @csrf

                @if ($errors->any())
                    <div class="tich-modal__errors">
                        <ul class="tich-list">
                            @foreach ($errors->all() as $error)
                                <li class="tich-text">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="tich-grid tich-grid--2">
                    <div>
                        <label for="document_type" class="tich-label">Document Type *</label>
                        <select id="document_type" name="document_type" required class="tich-input">
                            <option value="">Select type</option>
                            @foreach ($docTypeLabels as $type => $label)
                                <option value="{{ $type }}" @selected($preselectDocumentType === $type)>
                                    {{ $label }}@if (in_array($type, $requestedDocTypes, true)) (requested)@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="document_name" class="tich-label">Document Name *</label>
                        <input type="text" id="document_name" name="document_name" value="{{ old('document_name') }}" required class="tich-input">
                    </div>
                    <div>
                        <label for="file" class="tich-label">File *</label>
                        <input type="file" id="file" name="file" required class="tich-input" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,application/pdf,image/*">
                        <p class="tich-caption tich-mt-1">PDF, Word, or image — max 10 MB.</p>
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
                        <textarea id="notes" name="notes" rows="2" class="tich-input">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <footer class="tich-modal__footer">
                    <button type="submit" class="tich-btn tich-btn-primary">Upload Document</button>
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="upload-document-modal">Cancel</button>
                </footer>
            </form>
        </div>
    </div>

    @include('admin.partials.tich-modal-assets')
@endsection

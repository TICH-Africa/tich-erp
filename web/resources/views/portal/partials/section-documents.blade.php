@php
    $documentRequests = $documentRequests ?? collect();
    $documentTypes = $documentTypes ?? \App\Models\StudentDocumentRequest::TYPES;
    $openDocumentRequestModal = $errors->any();
@endphp

<x-page-toolbar title="Documents" meta="Application files and document requests">
    <x-slot:actions>
        <button type="button" class="tich-btn tich-btn-primary" data-open-modal="document-request-modal">
            Request a document
        </button>
    </x-slot:actions>
</x-page-toolbar>

<article class="tich-card tich-table-panel tich-mt-8">
    <h2 class="tich-h3" style="padding:1rem 1.25rem 0;">Application documents</h2>
    @if ($biodata['documents']->isEmpty())
        @include('partials.states.table-empty', ['colspan' => 4, 'title' => 'No documents on record yet', 'icon' => 'file-x'])
    @else
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>File</th>
                    <th>Uploaded</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($biodata['documents'] as $document)
                    <tr>
                        <td>{{ $document->displayLabel() }}</td>
                        <td>{{ $document->original_filename ?? $document->file_path ?? '-' }}</td>
                        <td>{{ $document->created_at?->format('d M Y') ?? '-' }}</td>
                        <td style="white-space:nowrap;">
                            @if ($document->isPreviewable())
                                <a href="{{ route('portal.documents.show', $document) }}" class="tich-link" target="_blank" rel="noopener">View</a>
                            @endif
                            <a href="{{ route('portal.documents.download', $document) }}" class="tich-link">Download</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</article>

@if ($documentRequests->isNotEmpty())
    <section class="tich-portal-panel tich-mt-8">
        <div class="tich-portal-panel__head">
            <h2 class="tich-h3">Your document requests</h2>
        </div>
        <div class="tich-card tich-table-panel tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Document</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($documentRequests as $item)
                        <tr>
                            <td>{{ $item->typeLabel() }}</td>
                            <td>{{ ucfirst($item->status) }}</td>
                            <td class="tich-caption">{{ $item->created_at?->format('d M Y') }}</td>
                            <td class="tich-caption">{{ $item->reviewer_notes ?: ($item->student_notes ?: '-') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif

<div
    id="document-request-modal"
    class="tich-modal{{ $openDocumentRequestModal ? ' is-open' : '' }}"
    aria-hidden="{{ $openDocumentRequestModal ? 'false' : 'true' }}"
    role="dialog"
    aria-modal="true"
    aria-labelledby="document-request-modal-title"
>
    <div class="tich-modal__backdrop" data-close-modal="document-request-modal"></div>
    <div class="tich-modal__dialog" style="max-width: 28rem;">
        <header class="tich-modal__header">
            <h2 id="document-request-modal-title" class="tich-h3" style="margin:0;">Request a document</h2>
            <button type="button" class="tich-modal__close" data-close-modal="document-request-modal" aria-label="Close">&times;</button>
        </header>
        <form method="POST" action="{{ route('portal.document-requests.store') }}" class="tich-modal__body">
            @csrf

            @if ($errors->any())
                <div class="tich-modal__errors tich-mb-4">
                    <ul style="margin:0; padding-left:1.25rem;">
                        @foreach ($errors->all() as $error)
                            <li class="tich-text">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <p class="tich-caption" style="margin-top:0;">Ask the registrar for certified transcripts, recommendation letters, or clearance forms.</p>

            <div style="display:grid; gap:1rem; margin-top:1rem;">
                <div class="tich-form-group" style="margin:0;">
                    <label for="document_type" class="tich-label">Document type</label>
                    <select id="document_type" name="document_type" class="tich-select" required>
                        @foreach ($documentTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('document_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group" style="margin:0;">
                    <label for="student_notes" class="tich-label">Notes</label>
                    <textarea id="student_notes" name="student_notes" rows="3" class="tich-input">{{ old('student_notes') }}</textarea>
                </div>
            </div>

            <footer class="tich-modal__footer">
                <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="document-request-modal">Cancel</button>
                <button type="submit" class="tich-btn tich-btn-primary">Submit request</button>
            </footer>
        </form>
    </div>
</div>

@include('admin.partials.tich-modal-assets')

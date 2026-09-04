<x-page-toolbar title="Documents" meta="Application files and document requests" />

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

@php
    $documentRequests = $documentRequests ?? collect();
    $documentTypes = $documentTypes ?? \App\Models\StudentDocumentRequest::TYPES;
@endphp

<article class="tich-card tich-mt-8">
    <h2 class="tich-h3">Request a document</h2>
    <p class="tich-caption tich-mt-2">Ask the registrar for certified transcripts, recommendation letters, or clearance forms.</p>
    <form method="POST" action="{{ route('portal.document-requests.store') }}" class="tich-form-stack tich-mt-4">
        @csrf
        <div>
            <label for="document_type" class="tich-label">Document type</label>
            <select id="document_type" name="document_type" class="tich-select" required>
                @foreach ($documentTypes as $value => $label)
                    <option value="{{ $value }}" @selected(old('document_type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="student_notes" class="tich-label">Notes</label>
            <textarea id="student_notes" name="student_notes" rows="3" class="tich-input">{{ old('student_notes') }}</textarea>
        </div>
        <button type="submit" class="tich-btn tich-btn-primary">Submit request</button>
    </form>
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

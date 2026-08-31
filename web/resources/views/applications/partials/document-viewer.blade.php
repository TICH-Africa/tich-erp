@php
    $documentRoutePrefix = $documentRoutePrefix ?? 'administration.applications';
    $previewableDocuments = $applicant->documents->filter(fn ($doc) => $doc->isPreviewable());
    $firstDocument = $previewableDocuments->first() ?? $applicant->documents->first();
@endphp

<article class="tich-card tich-mt-8">
    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 1rem; align-items: center;">
        <div>
            <h2 class="tich-h3">Document viewer</h2>
            <p class="tich-caption tich-mt-2">Preview uploaded application documents without leaving the dashboard.</p>
        </div>
        @if ($firstDocument)
            <a
                id="doc-viewer-open-tab"
                href="{{ route($documentRoutePrefix.'.documents.show', [$applicant->id, $firstDocument->id]) }}"
                target="_blank"
                rel="noopener"
                class="tich-link"
            >Open in new tab</a>
        @endif
    </div>

    @if ($applicant->documents->isEmpty())
        <p class="tich-caption tich-mt-4">No documents uploaded.</p>
    @else
        <div class="doc-viewer tich-mt-6">
            <aside class="doc-viewer__list" aria-label="Application documents">
                @foreach ($applicant->documents as $document)
                    <button
                        type="button"
                        class="doc-viewer__item{{ $loop->first ? ' is-active' : '' }}"
                        data-doc-id="{{ $document->id }}"
                        data-doc-label="{{ $document->displayLabel() }}"
                        data-doc-filename="{{ $document->original_filename }}"
                        data-doc-previewable="{{ $document->isPreviewable() ? '1' : '0' }}"
                        data-doc-mime="{{ $document->mime_type }}"
                        data-doc-view-url="{{ route($documentRoutePrefix.'.documents.show', [$applicant->id, $document->id]) }}"
                        data-doc-download-url="{{ route($documentRoutePrefix.'.documents.download', [$applicant->id, $document->id]) }}"
                    >
                        <span class="doc-viewer__item-label">{{ $document->displayLabel() }}</span>
                        <span class="doc-viewer__item-file">{{ $document->original_filename }}</span>
                    </button>
                @endforeach
            </aside>

            <div class="doc-viewer__panel">
                <div class="doc-viewer__toolbar">
                    <strong id="doc-viewer-title">{{ $firstDocument?->displayLabel() ?? 'Document' }}</strong>
                    <span id="doc-viewer-filename" class="tich-caption">{{ $firstDocument?->original_filename }}</span>
                    @if ($firstDocument)
                        <a
                            id="doc-viewer-download"
                            href="{{ route($documentRoutePrefix.'.documents.download', [$applicant->id, $firstDocument->id]) }}"
                            class="tich-btn tich-btn-secondary"
                            style="margin-left: auto;"
                        >Download</a>
                    @endif
                </div>

                <div id="doc-viewer-stage" class="doc-viewer__stage">
                    @if ($firstDocument && $firstDocument->isPreviewable())
                        @if (str_starts_with($firstDocument->mime_type, 'image/'))
                            <img
                                id="doc-viewer-image"
                                src="{{ route($documentRoutePrefix.'.documents.show', [$applicant->id, $firstDocument->id]) }}"
                                alt="{{ $firstDocument->displayLabel() }}"
                                class="doc-viewer__image"
                            >
                        @else
                            <iframe
                                id="doc-viewer-frame"
                                src="{{ route($documentRoutePrefix.'.documents.show', [$applicant->id, $firstDocument->id]) }}"
                                title="{{ $firstDocument->displayLabel() }}"
                                class="doc-viewer__frame"
                            ></iframe>
                        @endif
                    @elseif ($firstDocument)
                        <div class="doc-viewer__fallback">
                            <p class="tich-text">This file type cannot be previewed in the browser.</p>
                            <a href="{{ route($documentRoutePrefix.'.documents.download', [$applicant->id, $firstDocument->id]) }}" class="tich-btn tich-btn-primary tich-mt-4">Download file</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</article>

@include('applications.partials.document-viewer-styles')

@if ($applicant->documents->isNotEmpty())
@include('applications.partials.document-viewer-script')
@endif

@php
    $documents = $documents ?? $application->uploadedDocuments();
    $firstDocument = $documents->first(fn ($doc) => $doc['is_previewable']) ?? $documents->first();
    $viewerId = $viewerId ?? 'hr-doc-viewer';
    $showApplicationSelect = ! empty($applications) && $applications->count() > 1;
@endphp

<article class="tich-card tich-mt-8" id="{{ $viewerId }}">
    <div class="doc-viewer__header">
        <div>
            <h2 class="tich-h3">{{ $title ?? 'Document viewer' }}</h2>
            <p class="tich-caption tich-mt-2">{{ $subtitle ?? 'Preview application documents without leaving the dashboard.' }}</p>
        </div>
        @if ($showApplicationSelect)
            <div class="doc-viewer__application-select">
                <label for="{{ $viewerId }}-application" class="tich-label">Application</label>
                <select id="{{ $viewerId }}-application" class="tich-input">
                    @foreach ($applications as $appOption)
                        <option value="{{ $appOption->id }}" @selected(($selectedApplicationId ?? $applications->first()?->id) === $appOption->id)>
                            {{ $appOption->full_name }} - {{ $appOption->vacancy->job_title ?? $appOption->application_number }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>

    @if ($documents->isEmpty())
        <p class="tich-caption tich-mt-4">No documents uploaded for this application.</p>
    @else
        <div class="doc-viewer tich-mt-6" data-doc-viewer-root>
            <aside class="doc-viewer__list" aria-label="Application documents">
                @foreach ($documents as $document)
                    @php
                        $isActive = ($firstDocument['key'] ?? null) === $document['key'];
                        $viewUrl = route('hr.recruitment.documents.show', [$application, $document['key']]);
                        $downloadUrl = route('hr.recruitment.documents.download', [$application, $document['key']]);
                        $externalUrl = route('hr.recruitment.documents.viewer', [$application, $document['key']]);
                    @endphp
                    <button
                        type="button"
                        class="doc-viewer__item{{ $isActive ? ' is-active' : '' }}"
                        data-doc-key="{{ $document['key'] }}"
                        data-doc-label="{{ $document['label'] }}"
                        data-doc-filename="{{ $document['filename'] }}"
                        data-doc-previewable="{{ $document['is_previewable'] ? '1' : '0' }}"
                        data-doc-mime="{{ $document['mime_type'] }}"
                        data-doc-view-url="{{ $viewUrl }}"
                        data-doc-download-url="{{ $downloadUrl }}"
                        data-doc-external-url="{{ $externalUrl }}"
                    >
                        <span class="doc-viewer__item-label">{{ $document['label'] }}</span>
                        <span class="doc-viewer__item-file">{{ $document['filename'] }}</span>
                    </button>
                @endforeach
            </aside>

            <div class="doc-viewer__panel">
                <div class="doc-viewer__toolbar">
                    <div class="doc-viewer__toolbar-meta">
                        <strong data-doc-viewer-title>{{ $firstDocument['label'] ?? 'Document' }}</strong>
                        <span data-doc-viewer-filename class="tich-caption">{{ $firstDocument['filename'] ?? '' }}</span>
                    </div>
                    <div class="doc-viewer__toolbar-actions">
                        @if ($firstDocument)
                            <a
                                data-doc-viewer-external
                                href="{{ route('hr.recruitment.documents.viewer', [$application, $firstDocument['key']]) }}"
                                target="_blank"
                                rel="noopener"
                                class="tich-btn tich-btn-ghost"
                            >Open externally</a>
                            <button type="button" data-doc-viewer-print class="tich-btn tich-btn-secondary">Print</button>
                            <a
                                data-doc-viewer-download
                                href="{{ route('hr.recruitment.documents.download', [$application, $firstDocument['key']]) }}"
                                class="tich-btn tich-btn-secondary"
                            >Download</a>
                        @endif
                    </div>
                </div>

                <div data-doc-viewer-stage class="doc-viewer__stage">
                    @if ($firstDocument && $firstDocument['is_previewable'])
                        @if (str_starts_with($firstDocument['mime_type'], 'image/'))
                            <img
                                data-doc-viewer-image
                                src="{{ route('hr.recruitment.documents.show', [$application, $firstDocument['key']]) }}"
                                alt="{{ $firstDocument['label'] }}"
                                class="doc-viewer__image"
                            >
                        @else
                            <iframe
                                data-doc-viewer-frame
                                src="{{ route('hr.recruitment.documents.show', [$application, $firstDocument['key']]) }}"
                                title="{{ $firstDocument['label'] }}"
                                class="doc-viewer__frame"
                            ></iframe>
                        @endif
                    @elseif ($firstDocument)
                        <div class="doc-viewer__fallback">
                            <p class="tich-text">This file type cannot be previewed in the browser.</p>
                            <a href="{{ route('hr.recruitment.documents.download', [$application, $firstDocument['key']]) }}" class="tich-btn tich-btn-primary tich-mt-4">Download file</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if ($showApplicationSelect)
        <script type="application/json" id="{{ $viewerId }}-applications-data">
            {!! json_encode($applicationsPayload ?? []) !!}
        </script>
    @endif
</article>

@if ($documents->isNotEmpty() || $showApplicationSelect)
    <script src="{{ asset('js/tich-hr-document-viewer.js') }}"></script>
    <script>
        (function () {
            function boot() {
                if (!window.TichHrDocumentViewer) {
                    return;
                }

                @if ($showApplicationSelect)
                    window.TichHrDocumentViewer.initApplicationSwitcher(
                        '{{ $viewerId }}',
                        '{{ $viewerId }}-application',
                        '{{ $viewerId }}-applications-data'
                    );
                @else
                    window.TichHrDocumentViewer.init('{{ $viewerId }}');
                @endif
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', boot);
            } else {
                boot();
            }
        })();
    </script>
@endif

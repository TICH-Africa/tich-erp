@php
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
                href="{{ route('admissions.applications.documents.show', [$applicant->id, $firstDocument->id]) }}"
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
                        data-doc-view-url="{{ route('admissions.applications.documents.show', [$applicant->id, $document->id]) }}"
                        data-doc-download-url="{{ route('admissions.applications.documents.download', [$applicant->id, $document->id]) }}"
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
                            href="{{ route('admissions.applications.documents.download', [$applicant->id, $firstDocument->id]) }}"
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
                                src="{{ route('admissions.applications.documents.show', [$applicant->id, $firstDocument->id]) }}"
                                alt="{{ $firstDocument->displayLabel() }}"
                                class="doc-viewer__image"
                            >
                        @else
                            <iframe
                                id="doc-viewer-frame"
                                src="{{ route('admissions.applications.documents.show', [$applicant->id, $firstDocument->id]) }}"
                                title="{{ $firstDocument->displayLabel() }}"
                                class="doc-viewer__frame"
                            ></iframe>
                        @endif
                    @elseif ($firstDocument)
                        <div class="doc-viewer__fallback">
                            <p class="tich-text">This file type cannot be previewed in the browser.</p>
                            <a href="{{ route('admissions.applications.documents.download', [$applicant->id, $firstDocument->id]) }}" class="tich-btn tich-btn-primary tich-mt-4">Download file</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</article>

<style>
    .doc-viewer {
        display: grid;
        grid-template-columns: minmax(14rem, 18rem) minmax(0, 1fr);
        gap: 1rem;
        min-height: 32rem;
    }

    .doc-viewer__list {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        max-height: 40rem;
        overflow: auto;
        padding-right: 0.25rem;
    }

    .doc-viewer__item {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
        width: 100%;
        padding: 0.875rem 1rem;
        border: 1px solid var(--tich-border, #e2e4e5);
        border-radius: 0.375rem;
        background: var(--tich-surface, #fff);
        text-align: left;
        cursor: pointer;
        transition: border-color 0.15s ease, background 0.15s ease;
    }

    .doc-viewer__item:hover,
    .doc-viewer__item.is-active {
        border-color: var(--tich-blue, #1669a6);
        background: rgba(22, 105, 166, 0.06);
    }

    .doc-viewer__item-label {
        font-weight: 600;
        color: var(--tich-text, #494c50);
        font-size: 0.9375rem;
    }

    .doc-viewer__item-file {
        font-size: 0.75rem;
        color: #6b6e72;
        word-break: break-word;
    }

    .doc-viewer__panel {
        display: flex;
        flex-direction: column;
        min-width: 0;
        border: 1px solid var(--tich-border, #e2e4e5);
        border-radius: 0.375rem;
        overflow: hidden;
        background: #fafafa;
    }

    .doc-viewer__toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem 1rem;
        padding: 0.875rem 1rem;
        background: var(--tich-surface, #fff);
        border-bottom: 1px solid var(--tich-border, #e2e4e5);
    }

    .doc-viewer__stage {
        flex: 1;
        min-height: 28rem;
        background: #525659;
    }

    .doc-viewer__frame,
    .doc-viewer__image {
        width: 100%;
        height: 100%;
        min-height: 28rem;
        border: 0;
        display: block;
        object-fit: contain;
        background: #525659;
    }

    .doc-viewer__fallback {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 28rem;
        padding: 2rem;
        text-align: center;
        background: var(--tich-surface, #fff);
    }

    @media (max-width: 900px) {
        .doc-viewer {
            grid-template-columns: 1fr;
        }

        .doc-viewer__list {
            max-height: none;
        }
    }
</style>

@if ($applicant->documents->isNotEmpty())
<script>
(function () {
    var items = document.querySelectorAll('.doc-viewer__item');
    var stage = document.getElementById('doc-viewer-stage');
    var title = document.getElementById('doc-viewer-title');
    var filename = document.getElementById('doc-viewer-filename');
    var download = document.getElementById('doc-viewer-download');
    var openTab = document.getElementById('doc-viewer-open-tab');

    if (!items.length || !stage) {
        return;
    }

    items.forEach(function (item) {
        item.addEventListener('click', function () {
            items.forEach(function (button) {
                button.classList.remove('is-active');
            });
            item.classList.add('is-active');

            var label = item.dataset.docLabel || 'Document';
            var name = item.dataset.docFilename || '';
            var viewUrl = item.dataset.docViewUrl;
            var downloadUrl = item.dataset.docDownloadUrl;
            var previewable = item.dataset.docPreviewable === '1';
            var mime = item.dataset.docMime || '';

            title.textContent = label;
            filename.textContent = name;

            if (download) {
                download.href = downloadUrl;
            }

            if (openTab) {
                openTab.href = viewUrl;
            }

            stage.innerHTML = '';

            if (!previewable) {
                stage.innerHTML =
                    '<div class="doc-viewer__fallback">' +
                    '<p class="tich-text">This file type cannot be previewed in the browser.</p>' +
                    '<a href="' + downloadUrl + '" class="tich-btn tich-btn-primary tich-mt-4">Download file</a>' +
                    '</div>';
                return;
            }

            if (mime.indexOf('image/') === 0) {
                var image = document.createElement('img');
                image.id = 'doc-viewer-image';
                image.className = 'doc-viewer__image';
                image.src = viewUrl;
                image.alt = label;
                stage.appendChild(image);
                return;
            }

            var frame = document.createElement('iframe');
            frame.id = 'doc-viewer-frame';
            frame.className = 'doc-viewer__frame';
            frame.src = viewUrl;
            frame.title = label;
            stage.appendChild(frame);
        });
    });
})();
</script>
@endif

@php
    use App\Support\SupportingDocumentResponse;

    $documents = collect($documents ?? [])
        ->values()
        ->map(function ($file, $index) use ($viewRoute, $downloadRoute, $routeParams) {
            if (! is_array($file)) {
                return null;
            }

            $name = (string) ($file['original_name'] ?? ('File '.($index + 1)));
            $mime = (string) ($file['mime'] ?? '');
            $params = array_merge($routeParams, ['index' => $index]);

            return [
                'index' => $index,
                'label' => 'Attachment '.($index + 1),
                'filename' => $name,
                'mime' => $mime,
                'is_previewable' => SupportingDocumentResponse::isPreviewable($mime, $name),
                'view_url' => route($viewRoute, $params),
                'download_url' => route($downloadRoute, $params),
            ];
        })
        ->filter()
        ->values();

    $firstDocument = $documents->first(fn ($doc) => $doc['is_previewable']) ?? $documents->first();
    $title = $title ?? 'Supporting documents';
    $subtitle = $subtitle ?? 'Preview uploaded files without leaving the platform.';
@endphp

<article class="tich-card tich-mt-8">
    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 1rem; align-items: center;">
        <div>
            <h2 class="tich-h3">{{ $title }}</h2>
            <p class="tich-caption tich-mt-2">{{ $subtitle }}</p>
        </div>
        @if ($firstDocument)
            <a
                id="doc-viewer-open-tab"
                href="{{ $firstDocument['view_url'] }}"
                target="_blank"
                rel="noopener"
                class="tich-link"
            >Open in new tab</a>
        @endif
    </div>

    @if ($documents->isEmpty())
        <p class="tich-caption tich-mt-4">No supporting documents uploaded.</p>
    @else
        <div class="doc-viewer tich-mt-6">
            <aside class="doc-viewer__list" aria-label="Supporting documents">
                @foreach ($documents as $document)
                    <button
                        type="button"
                        class="doc-viewer__item{{ ($firstDocument['index'] ?? null) === $document['index'] ? ' is-active' : '' }}"
                        data-doc-id="{{ $document['index'] }}"
                        data-doc-label="{{ $document['label'] }}"
                        data-doc-filename="{{ $document['filename'] }}"
                        data-doc-previewable="{{ $document['is_previewable'] ? '1' : '0' }}"
                        data-doc-mime="{{ $document['mime'] }}"
                        data-doc-view-url="{{ $document['view_url'] }}"
                        data-doc-download-url="{{ $document['download_url'] }}"
                    >
                        <span class="doc-viewer__item-label">{{ $document['label'] }}</span>
                        <span class="doc-viewer__item-file">{{ $document['filename'] }}</span>
                    </button>
                @endforeach
            </aside>

            <div class="doc-viewer__panel">
                <div class="doc-viewer__toolbar">
                    <strong id="doc-viewer-title">{{ $firstDocument['label'] ?? 'Document' }}</strong>
                    <span id="doc-viewer-filename" class="tich-caption">{{ $firstDocument['filename'] ?? '' }}</span>
                    @if ($firstDocument)
                        <a
                            id="doc-viewer-download"
                            href="{{ $firstDocument['download_url'] }}"
                            class="tich-btn tich-btn-secondary"
                            style="margin-left: auto;"
                        >Download</a>
                    @endif
                </div>

                <div id="doc-viewer-stage" class="doc-viewer__stage">
                    @if ($firstDocument && $firstDocument['is_previewable'])
                        @if (str_starts_with($firstDocument['mime'], 'image/'))
                            <img
                                id="doc-viewer-image"
                                src="{{ $firstDocument['view_url'] }}"
                                alt="{{ $firstDocument['label'] }}"
                                class="doc-viewer__image"
                            >
                        @else
                            <iframe
                                id="doc-viewer-frame"
                                src="{{ $firstDocument['view_url'] }}"
                                title="{{ $firstDocument['label'] }}"
                                class="doc-viewer__frame"
                            ></iframe>
                        @endif
                    @elseif ($firstDocument)
                        <div class="doc-viewer__fallback">
                            <p class="tich-text">This file type cannot be previewed in the browser.</p>
                            <a href="{{ $firstDocument['download_url'] }}" class="tich-btn tich-btn-primary tich-mt-4">Download file</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</article>

@include('applications.partials.document-viewer-styles')

@if ($documents->isNotEmpty())
    @include('applications.partials.document-viewer-script')
@endif

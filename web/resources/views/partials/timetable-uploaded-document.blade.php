@php
    $pdfUrl = $pdfUrl ?? null;
    $fileLabel = $fileLabel ?? ($timetable->source_name ?? 'Uploaded timetable');
    $documentService = app(\App\Services\ProgramTimetableDocumentService::class);
    $previewService = app(\App\Services\TimetableDocumentPreviewService::class);
    $mime = $pdfUrl ? strtolower($documentService->mimeType($timetable)) : '';
    $isPdf = str_contains($mime, 'pdf');
    $isImage = str_starts_with($mime, 'image/');
    $documentHtml = ($pdfUrl && ! $isPdf && ! $isImage) ? $previewService->html($timetable) : null;
    $fallbackReason = ($pdfUrl && ! $isPdf && ! $isImage && ! $documentHtml)
        ? ($previewService->fallbackReason($timetable) ?: 'Preview is not available for this file type in the browser.')
        : null;
@endphp

<div class="tich-mt-6">
    <p class="tich-caption tich-mb-4">
        This timetable is shown exactly as uploaded by the academic office
        @if ($fileLabel)
            (<span class="tich-text">{{ $fileLabel }}</span>).
        @else
            .
        @endif
    </p>
    @if ($pdfUrl)
        <div class="doc-viewer doc-viewer--single doc-viewer--timetable tich-mt-2">
            <div class="doc-viewer__panel doc-viewer__panel--full">
                <div class="doc-viewer__stage{{ $documentHtml ? ' doc-viewer__stage--document' : '' }}">
                    @if ($isPdf)
                        <iframe
                            src="{{ $pdfUrl }}"
                            title="{{ $timetable->displayTitle() }}"
                            class="doc-viewer__frame"
                        ></iframe>
                    @elseif ($isImage)
                        <img
                            src="{{ $pdfUrl }}"
                            alt="{{ $timetable->displayTitle() }}"
                            class="doc-viewer__image"
                        />
                    @elseif ($documentHtml)
                        <div class="doc-viewer__paper">
                            {!! $documentHtml !!}
                        </div>
                    @else
                        <div class="doc-viewer__fallback">
                            <p class="tich-text tich-mb-4">{{ $fallbackReason }}</p>
                            <a href="{{ $pdfUrl }}" class="tich-btn tich-btn-secondary" download>Open / download document</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @if ($documentHtml)
            <p class="tich-caption tich-mt-2">
                <a href="{{ $pdfUrl }}" class="tich-link" download>Download the uploaded file</a>
            </p>
        @endif
    @else
        <p class="tich-text">The uploaded file is not available right now.</p>
    @endif
</div>

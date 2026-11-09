@php
    $documentHtml = app(\App\Services\TimetableDocumentPreviewService::class)->html($timetable);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $documentTitle ?? 'Timetable' }}</title>
    <style>
        html, body { margin: 0; height: 100%; }
        iframe { border: 0; width: 100%; height: 100%; }
        .doc-print { padding: 1rem; color: #2f3234; font-family: system-ui, sans-serif; font-size: 0.875rem; line-height: 1.5; }
        .doc-print h3, .doc-print h4, .doc-print h5, .doc-print h6 { margin: 1rem 0 0.5rem; }
        .doc-print p { margin: 0 0 0.65rem; }
        .doc-print .doc-viewer__doc-scroll { overflow-x: auto; margin-bottom: 1rem; }
        .doc-print table { border-collapse: collapse; width: 100%; }
        .doc-print th, .doc-print td { border: 1px solid #b9bec2; padding: 0.35rem 0.4rem; text-align: left; vertical-align: top; }
        .doc-print th { background: #f2f4f5; }
        .doc-print .doc-viewer__doc-note { font-style: italic; color: #6b6e72; }
        .doc-print .doc-viewer__swipe-hint { display: none; font-size: 0.75rem; color: #6b6e72; margin: 0 0 0.5rem; }
        /* A timetable grid keeps its columns on a phone and swipes sideways instead of squeezing unreadable. */
        @media (max-width: 640px) {
            .doc-print { padding: 0.7rem 0.6rem; font-size: 0.8125rem; }
            .doc-print table { width: auto; min-width: 100%; }
            .doc-print th, .doc-print td { font-size: 0.8125rem; padding: 0.35rem 0.4rem; min-width: 6rem; }
            .doc-print th:first-child, .doc-print td:first-child {
                position: sticky; left: 0; z-index: 1; background: #fff; border-left: 0; min-width: 6.25rem;
            }
            .doc-print thead th:first-child { background: #f2f4f5; }
            .doc-print .doc-viewer__swipe-hint { display: block; }
        }
        @media print {
            html, body { height: auto; }
            .doc-print { padding: 0; font-size: 8.5pt; }
            .doc-print .doc-viewer__doc-scroll { overflow: visible; }
            .doc-print table { width: 100%; min-width: 0; }
            .doc-print th, .doc-print td { min-width: 0; padding: 2pt 3pt; font-size: 8.5pt; position: static; }
            .doc-print .doc-viewer__swipe-hint { display: none; }
        }
    </style>
</head>
<body>
    @if ($documentHtml)
        <div class="doc-print">
            <p class="doc-viewer__swipe-hint">Swipe a table sideways to see the rest of it.</p>
            {!! $documentHtml !!}
        </div>
    @else
        <iframe src="{{ $pdfUrl }}" title="{{ $documentTitle ?? 'Timetable' }}"></iframe>
    @endif
</body>
</html>

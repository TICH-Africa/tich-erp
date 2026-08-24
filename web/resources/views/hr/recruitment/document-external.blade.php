<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $document['label'] }} - {{ $application->full_name }}</title>
    <link rel="stylesheet" href="{{ asset('css/tich-platform.css') }}">
    @if (config('security.block_inspect_ui', true))
        <script src="{{ asset('js/tich-ui-protection.js') }}" defer></script>
    @endif
    <style>
        html, body {
            margin: 0;
            height: 100%;
            background: #1f2937;
        }

        .doc-external-viewer {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .doc-external-viewer__toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.75rem 1rem;
            padding: 0.875rem 1rem;
            background: #fff;
            border-bottom: 1px solid var(--tich-border, #e2e4e5);
        }

        .doc-external-viewer__meta {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
            min-width: 0;
            flex: 1 1 12rem;
        }

        .doc-external-viewer__meta strong {
            font-size: 0.9375rem;
        }

        .doc-external-viewer__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-left: auto;
        }

        .doc-external-viewer__stage {
            flex: 1;
            min-height: 0;
            background: #525659;
        }

        .doc-external-viewer__frame,
        .doc-external-viewer__image {
            width: 100%;
            height: calc(100vh - 4.5rem);
            border: 0;
            display: block;
            object-fit: contain;
            background: #525659;
        }

        .doc-external-viewer__fallback {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 4.5rem);
            padding: 2rem;
            text-align: center;
            background: #fff;
        }

        @media print {
            .doc-external-viewer__toolbar {
                display: none;
            }

            .doc-external-viewer__frame,
            .doc-external-viewer__image {
                height: auto;
                min-height: 100vh;
            }
        }
    </style>
</head>
<body class="doc-external-viewer">
    <header class="doc-external-viewer__toolbar">
        <div class="doc-external-viewer__meta">
            <strong>{{ $document['label'] }}</strong>
            <span class="tich-caption">{{ $document['filename'] }} · {{ $application->full_name }}</span>
        </div>
        <div class="doc-external-viewer__actions">
            <a href="{{ route('hr.recruitment.show', $application) }}" class="tich-btn tich-btn-ghost">&larr; Back to application</a>
            @if ($document['is_previewable'])
                <button type="button" class="tich-btn tich-btn-secondary" onclick="printDocument()">Print</button>
            @endif
            <a href="{{ route('hr.recruitment.documents.download', [$application, $document['key']]) }}" class="tich-btn tich-btn-primary">Download</a>
        </div>
    </header>

    <main class="doc-external-viewer__stage">
        @if ($document['is_previewable'])
            @if (str_starts_with($document['mime_type'], 'image/'))
                <img
                    id="doc-external-image"
                    src="{{ route('hr.recruitment.documents.show', [$application, $document['key']]) }}"
                    alt="{{ $document['label'] }}"
                    class="doc-external-viewer__image"
                >
            @else
                <iframe
                    id="doc-external-frame"
                    src="{{ route('hr.recruitment.documents.show', [$application, $document['key']]) }}"
                    title="{{ $document['label'] }}"
                    class="doc-external-viewer__frame"
                ></iframe>
            @endif
        @else
            <div class="doc-external-viewer__fallback">
                <p class="tich-text">This file type cannot be previewed in the browser.</p>
                <a href="{{ route('hr.recruitment.documents.download', [$application, $document['key']]) }}" class="tich-btn tich-btn-primary tich-mt-4">Download file</a>
            </div>
        @endif
    </main>

    @if ($document['is_previewable'])
        <script>
            function printDocument() {
                var frame = document.getElementById('doc-external-frame');
                var image = document.getElementById('doc-external-image');

                if (frame && frame.contentWindow) {
                    frame.contentWindow.focus();
                    frame.contentWindow.print();
                    return;
                }

                window.print();
            }
        </script>
    @endif
</body>
</html>

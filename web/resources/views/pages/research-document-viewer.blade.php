<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $document->title }} · Read only · TICH Research</title>
    <style>
        :root {
            --bg: #1e2126;
            --panel: #2a2f36;
            --text: #f3f4f6;
            --muted: #9aa3b0;
            --accent: #5b9bd5;
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            height: 100%;
            background: var(--bg);
            color: var(--text);
            font-family: "Segoe UI", system-ui, sans-serif;
            user-select: none;
            -webkit-user-select: none;
            overflow: hidden;
        }
        .rv-shell {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .rv-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.75rem 1rem;
            padding: 0.75rem 1rem;
            background: var(--panel);
            border-bottom: 1px solid #3a414b;
        }
        .rv-toolbar h1 {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 600;
            flex: 1;
            min-width: 12rem;
        }
        .rv-toolbar .meta { color: var(--muted); font-size: 0.75rem; }
        .rv-toolbar button {
            background: #3a414b;
            color: var(--text);
            border: 0;
            border-radius: 4px;
            padding: 0.4rem 0.7rem;
            cursor: pointer;
        }
        .rv-toolbar button:hover { background: #4a5360; }
        .rv-stage {
            position: relative;
            flex: 1;
            overflow: auto;
            padding: 1.25rem;
        }
        .rv-pages {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }
        .rv-page-wrap {
            position: relative;
            box-shadow: 0 8px 28px rgba(0,0,0,0.35);
            background: #fff;
        }
        .rv-page-wrap canvas { display: block; max-width: 100%; height: auto; }
        .rv-watermark {
            pointer-events: none;
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transform: rotate(-28deg);
            font-size: clamp(0.7rem, 1.6vw, 1rem);
            color: rgba(30, 60, 90, 0.18);
            font-weight: 700;
            letter-spacing: 0.04em;
            text-align: center;
            padding: 2rem;
            white-space: pre-line;
        }
        .rv-image {
            max-width: min(900px, 100%);
            max-height: calc(100vh - 6rem);
            display: block;
            margin: 0 auto;
            user-select: none;
            -webkit-user-drag: none;
        }
        .rv-fallback {
            max-width: 36rem;
            margin: 3rem auto;
            text-align: center;
            color: var(--muted);
            line-height: 1.5;
        }
        .rv-error { color: #f5a8a8; text-align: center; margin-top: 2rem; }
    </style>
</head>
<body
    data-research-viewer
    data-stream-url="{{ $streamUrl }}"
    data-is-pdf="{{ $document->isPdf() ? '1' : '0' }}"
    data-is-image="{{ $document->isImage() ? '1' : '0' }}"
    data-watermark="TICH Research Repository — Read Only — {{ $viewerIp }} / {{ $viewerStamp }}"
>
<div class="rv-shell">
    <header class="rv-toolbar">
        <h1>{{ $document->title }}</h1>
        <span class="meta">{{ $activity->title }} · Read only</span>
        <button type="button" data-zoom-out title="Zoom out">−</button>
        <button type="button" data-zoom-in title="Zoom in">+</button>
        <a href="{{ route('research.show', $activity->slug) }}" class="meta" style="color:var(--accent);text-decoration:none;">Back to activity</a>
    </header>
    <main class="rv-stage" id="rv-stage">
        @if ($document->isPdf())
            <div class="rv-pages" id="rv-pages"></div>
            <p class="rv-error" id="rv-error" hidden></p>
        @elseif ($document->isImage())
            <div class="rv-page-wrap" style="margin:0 auto;">
                <img src="{{ $streamUrl }}" alt="{{ $document->title }}" class="rv-image" draggable="false">
                <div class="rv-watermark">{{ 'TICH Research Repository — Read Only — '.$viewerIp.' / '.$viewerStamp }}</div>
            </div>
        @else
            <div class="rv-fallback">
                <p>This file type cannot be rendered in the secure viewer.</p>
                <p>Please contact the Research office for access. Downloads are not available from this portal.</p>
            </div>
        @endif
    </main>
</div>

@if ($document->isPdf())
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.4.168/pdf.min.mjs" type="module"></script>
<script type="module">
import * as pdfjsLib from 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.4.168/pdf.min.mjs';
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.4.168/pdf.worker.min.mjs';

const root = document.body;
const streamUrl = root.dataset.streamUrl;
const watermark = root.dataset.watermark || '';
const pagesEl = document.getElementById('rv-pages');
const errorEl = document.getElementById('rv-error');
let scale = 1.15;

function blockEvent(e) {
    e.preventDefault();
    e.stopPropagation();
    return false;
}

document.addEventListener('contextmenu', blockEvent);
document.addEventListener('dragstart', blockEvent);
document.addEventListener('keydown', function (e) {
    const key = (e.key || '').toLowerCase();
    if ((e.ctrlKey || e.metaKey) && ['s', 'p', 'u', 'c', 'a'].includes(key)) {
        blockEvent(e);
    }
    if (key === 'f12' || key === 'printscreen') {
        blockEvent(e);
    }
});

async function render() {
    try {
        const loading = pdfjsLib.getDocument({
            url: streamUrl,
            withCredentials: true,
            disableStream: false,
            disableAutoFetch: false,
        });
        const pdf = await loading.promise;
        pagesEl.innerHTML = '';
        for (let i = 1; i <= pdf.numPages; i++) {
            const page = await pdf.getPage(i);
            const viewport = page.getViewport({ scale });
            const wrap = document.createElement('div');
            wrap.className = 'rv-page-wrap';
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            wrap.appendChild(canvas);
            const mark = document.createElement('div');
            mark.className = 'rv-watermark';
            mark.textContent = watermark;
            wrap.appendChild(mark);
            pagesEl.appendChild(wrap);
            await page.render({ canvasContext: ctx, viewport }).promise;
        }
    } catch (err) {
        errorEl.hidden = false;
        errorEl.textContent = 'Unable to open this document in the secure viewer.';
        console.error(err);
    }
}

document.querySelector('[data-zoom-in]')?.addEventListener('click', function () {
    scale = Math.min(scale + 0.15, 2.5);
    render();
});
document.querySelector('[data-zoom-out]')?.addEventListener('click', function () {
    scale = Math.max(scale - 0.15, 0.6);
    render();
});

render();
</script>
@else
<script>
(function () {
    function blockEvent(e) { e.preventDefault(); e.stopPropagation(); return false; }
    document.addEventListener('contextmenu', blockEvent);
    document.addEventListener('dragstart', blockEvent);
    document.addEventListener('keydown', function (e) {
        var key = (e.key || '').toLowerCase();
        if ((e.ctrlKey || e.metaKey) && ['s', 'p', 'u', 'c', 'a'].includes(key)) blockEvent(e);
        if (key === 'f12' || key === 'printscreen') blockEvent(e);
    });
})();
</script>
@endif
</body>
</html>

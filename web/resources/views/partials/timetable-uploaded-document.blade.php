@php
    $pdfUrl = $pdfUrl ?? null;
    $fileLabel = $fileLabel ?? ($timetable->source_name ?? 'Uploaded timetable');
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
        <div class="doc-viewer" style="min-height:32rem;">
            <iframe
                src="{{ $pdfUrl }}"
                title="{{ $timetable->displayTitle() }}"
                style="width:100%; min-height:32rem; border:1px solid var(--tich-border); border-radius:0.5rem; background:#fff;"
            ></iframe>
        </div>
    @else
        <p class="tich-text">The uploaded file is not available right now.</p>
    @endif
</div>

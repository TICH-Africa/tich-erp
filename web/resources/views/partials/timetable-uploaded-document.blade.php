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
        @php
            $mime = app(\App\Services\ProgramTimetableDocumentService::class)->mimeType($timetable);
            $isPdf = str_contains(strtolower($mime), 'pdf');
            $isImage = str_starts_with(strtolower($mime), 'image/');
        @endphp
        <div class="doc-viewer" style="min-height:32rem;">
            @if ($isPdf)
                <iframe
                    src="{{ $pdfUrl }}"
                    title="{{ $timetable->displayTitle() }}"
                    style="width:100%; min-height:32rem; border:1px solid var(--tich-border); border-radius:0.5rem; background:#fff;"
                ></iframe>
            @elseif ($isImage)
                <img
                    src="{{ $pdfUrl }}"
                    alt="{{ $timetable->displayTitle() }}"
                    style="width:100%; max-width:100%; height:auto; border:1px solid var(--tich-border); border-radius:0.5rem; background:#fff;"
                />
            @else
                <p class="tich-text tich-mb-4">Preview is not available for this file type in the browser.</p>
                <a href="{{ $pdfUrl }}" class="tich-btn tich-btn-secondary" download>Open / download document</a>
            @endif
        </div>
    @else
        <p class="tich-text">The uploaded file is not available right now.</p>
    @endif
</div>

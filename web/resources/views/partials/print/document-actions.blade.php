@if (! empty($backUrl) || ! empty($printUrl) || ! empty($pdfUrl) || ! empty($pdfViewUrl) || ! empty($pdfDownloadUrl) || ! empty($excelDownloadUrl))
    <nav class="tich-doc-actions" aria-label="Document actions">
        @if (! empty($backUrl))
            <a href="{{ $backUrl }}">&larr; Back</a>
        @endif
        <button type="button" class="primary" onclick="window.print()">Print</button>
        @if (! empty($pdfViewUrl))
            <a href="{{ $pdfViewUrl }}" target="_blank" rel="noopener">View PDF</a>
        @endif
        @if (! empty($pdfDownloadUrl))
            <a href="{{ $pdfDownloadUrl }}" class="primary">Download PDF</a>
        @elseif (! empty($pdfUrl))
            <a href="{{ $pdfUrl }}" class="primary">Download</a>
        @endif
        @if (! empty($excelDownloadUrl))
            <a href="{{ $excelDownloadUrl }}">Download Excel</a>
        @endif
    </nav>
@endif

@if (! empty($backUrl) || ! empty($printUrl) || ! empty($pdfUrl))
    <nav class="tich-doc-actions" aria-label="Document actions">
        @if (! empty($backUrl))
            <a href="{{ $backUrl }}">&larr; Back</a>
        @endif
        <button type="button" class="primary" onclick="window.print()">Print</button>
        @if (! empty($pdfUrl))
            <a href="{{ $pdfUrl }}" class="primary">Download PDF</a>
        @endif
    </nav>
@endif

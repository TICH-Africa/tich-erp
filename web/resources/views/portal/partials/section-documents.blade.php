<header class="tich-dept-header">
    <p class="tich-caption">My services</p>
    <h1 class="tich-h1 tich-dept-header__title">Documents</h1>
    <p class="tich-text tich-dept-header__meta">Files submitted with your application.</p>
</header>

<article class="tich-card tich-mt-8" style="overflow-x: auto;">
    @if ($biodata['documents']->isEmpty())
        <p class="tich-text">No documents on record yet.</p>
    @else
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>File</th>
                    <th>Uploaded</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($biodata['documents'] as $document)
                    <tr>
                        <td>{{ $document->displayLabel() }}</td>
                        <td>{{ $document->original_filename ?? $document->file_path ?? '-' }}</td>
                        <td>{{ $document->created_at?->format('d M Y') ?? '-' }}</td>
                        <td style="white-space:nowrap;">
                            @if ($document->isPreviewable())
                                <a href="{{ route('portal.documents.show', $document) }}" class="tich-link" target="_blank" rel="noopener">View</a>
                            @endif
                            <a href="{{ route('portal.documents.download', $document) }}" class="tich-link">Download</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</article>

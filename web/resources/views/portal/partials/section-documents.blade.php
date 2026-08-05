<x-page-toolbar title="Documents" meta="Files submitted with your application" />

<article class="tich-card tich-table-panel tich-mt-8">
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

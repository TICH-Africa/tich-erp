<x-page-toolbar title="Learning content" meta="Notes, slides, and reference materials" />

<div class="tich-grid tich-grid--2 tich-mt-6" style="align-items:start; gap:1.5rem;">
    <article class="tich-card">
        <h2 class="tich-h3">Upload material</h2>
        @if ($portalData['allocations']->isEmpty())
            <p class="tich-text tich-mt-4">No units assigned.</p>
        @else
            <form method="POST" action="{{ route('staff.content.store') }}" enctype="multipart/form-data" class="tich-mt-4">
                @csrf
                <div class="tich-form-group">
                    <label class="tich-label">Unit</label>
                    <select name="unit_id" class="tich-input" required>
                        @foreach ($portalData['allocations'] as $allocation)
                            <option value="{{ $allocation->unit_id }}">{{ $allocation->unit?->unit_code }} - {{ $allocation->unit?->unit_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Title</label>
                    <input type="text" name="title" class="tich-input" required>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Short notes / description</label>
                    <textarea name="content_text" class="tich-input" rows="3" placeholder="Brief notes for students..."></textarea>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Attachment (optional)</label>
                    <input type="file" name="file" class="tich-input">
                    <p class="tich-caption tich-mt-1">Leave blank if posting notes only.</p>
                </div>
                <button type="submit" class="tich-btn tich-btn-primary">Post to students</button>
            </form>
        @endif
    </article>

    <article class="tich-card">
        <h2 class="tich-h3">Uploaded materials</h2>
        @if ($portalData['learning_content']->isEmpty())
            <p class="tich-text tich-mt-4">No materials uploaded yet.</p>
        @else
            <div class="tich-table-panel tich-mt-4">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Posted</th>
                            <th>File</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($portalData['learning_content'] as $item)
                            <tr>
                                <td>{{ $item->unit->unit_code ?? '-' }} {{ $item->unit->unit_name ?? '' }}</td>
                                <td>
                                    <strong>{{ $item->title }}</strong>
                                    @if ($item->content_text)
                                        <div class="tich-caption tich-mt-1">{{ Str::limit($item->content_text, 80) }}</div>
                                    @endif
                                </td>
                                <td class="tich-caption">{{ ucfirst(str_replace('_', ' ', $item->content_type)) }}</td>
                                <td class="tich-caption">{{ $item->published_at?->format('d M Y') ?? $item->created_at->format('d M Y') }}</td>
                                <td>
                                    @if ($item->file_path)
                                        <a href="{{ asset('storage/' . $item->file_path) }}" target="_blank" class="tich-link">Download</a>
                                        <span class="tich-caption">{{ number_format($item->file_size / 1024, 1) }} KB</span>
                                    @else
                                        <span class="tich-caption">Notes only</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </article>
</div>

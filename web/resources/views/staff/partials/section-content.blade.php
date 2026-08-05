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
                    <label class="tich-label">Caption</label>
                    <input type="text" name="caption" class="tich-input">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">File</label>
                    <input type="file" name="file" class="tich-input" required>
                </div>
                <button type="submit" class="tich-btn tich-btn-primary">Upload</button>
            </form>
        @endif
    </article>

    <article class="tich-card">
        <h2 class="tich-h3">Uploaded materials</h2>
        @if ($portalData['learning_content']->isEmpty())
            <p class="tich-text tich-mt-4">No materials uploaded yet.</p>
        @else
            <ul class="tich-mt-4">
                @foreach ($portalData['learning_content'] as $item)
                    <li class="tich-text tich-mb-2">
                        <strong>{{ $item->title }}</strong>
                        @if ($item->caption)<br><span class="tich-caption">{{ $item->caption }}</span>@endif
                    </li>
                @endforeach
            </ul>
        @endif
    </article>
</div>

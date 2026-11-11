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
                    <label class="tich-label">Section or module name</label>
                    <input type="text" name="section_title" class="tich-input" maxlength="300"
                           value="{{ old('section_title') }}"
                           placeholder="e.g. Module 2 - Web Application Developing">
                    <p class="tich-caption tich-mt-1">Optional. Notes with the same name are read together by students.</p>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Lesson number</label>
                    <input type="number" name="lesson_number" class="tich-input" min="1" max="9999"
                           value="{{ old('lesson_number') }}" placeholder="e.g. 3">
                    <p class="tich-caption tich-mt-1">Optional. Leave blank and the note sits below the numbered lessons, oldest first.</p>
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
                    <label class="tich-label">Attachment <span style="color:var(--tich-danger, #dc2626);">*</span></label>
                    <input type="file" name="file" class="tich-input" required>
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
            @php
                // Students read a unit section by section, so the lecturer sees the same order they set.
                $byUnit = $portalData['learning_content']
                    ->groupBy(fn ($item) => ($item->unit?->unit_code ?? 'Unit').' - '.($item->unit?->unit_name ?? 'Learning content'))
                    ->map(fn ($items) => App\Models\UnitContent::arrange($items)
                        ->groupBy(fn ($item) => $item->sectionLabel()));
            @endphp

            <div class="tich-unit-modules tich-mt-4">
                @foreach ($byUnit as $unitLabel => $sections)
                    <details class="tich-unit-module" @if($loop->first) open @endif>
                        <summary class="tich-unit-module__head">
                            <span class="tich-unit-module__title">{{ $unitLabel }}</span>
                            <span class="tich-unit-module__count">{{ $sections->sum(fn ($items) => $items->count()) }} posted</span>
                        </summary>
                        <div class="tich-unit-module__panel">
                            @include('partials.learning-lessons', [
                                'sections' => $sections,
                                'rowMode' => 'actions',
                                'viewRoute' => 'staff.content.view',
                                'downloadRoute' => 'staff.content.download',
                            ])
                        </div>
                    </details>
                @endforeach
            </div>
        @endif
    </article>
</div>

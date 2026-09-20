@props([
    'activity' => null,
    'durationUnits' => [],
    'lifecycleStatuses' => [],
    'visibilities' => [],
    'uploadUrl' => null,
])

@php
    $bodyValue = old('body', $activity->body ?? '');
    $statusLocked = (bool) old('status_locked', $activity->status_locked ?? false);
@endphp

<div class="uf-form-section">
    <div class="uf-section-head">Activity metadata</div>
    <div class="uf-section-body">
        <div class="uf-form-grid-2">
            <div class="uf-field" style="grid-column: 1 / -1;">
                <label for="title">Title <span class="uf-req">*</span></label>
                <input id="title" type="text" name="title" value="{{ old('title', $activity->title ?? '') }}" required maxlength="300">
            </div>
            <div class="uf-field" style="grid-column: 1 / -1;">
                <label for="subtitle">Subtitle</label>
                <input id="subtitle" type="text" name="subtitle" value="{{ old('subtitle', $activity->subtitle ?? '') }}" maxlength="500">
            </div>
            <div class="uf-field" style="grid-column: 1 / -1;">
                <label for="summary">Short summary <span class="uf-req">*</span></label>
                <textarea id="summary" name="summary" rows="3" required maxlength="5000">{{ old('summary', $activity->summary ?? '') }}</textarea>
                <span class="uf-hint">Shown on the public Research portal cards.</span>
            </div>
            <div class="uf-field">
                <label for="visibility">Visibility <span class="uf-req">*</span></label>
                <select id="visibility" name="visibility" required>
                    @foreach ($visibilities as $vis)
                        <option value="{{ $vis }}" @selected(old('visibility', $activity->visibility ?? 'draft') === $vis)>{{ ucfirst($vis) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="uf-field">
                <label for="cover_image">Cover image</label>
                <input id="cover_image" type="file" name="cover_image" accept="image/jpeg,image/png,image/webp,image/gif">
                @if ($activity?->coverUrl())
                    <div class="uf-field--check tich-mt-2" style="flex-wrap:nowrap;">
                        <img src="{{ $activity->coverUrl() }}" alt="" style="width:4.5rem;height:3rem;object-fit:cover;border-radius:4px;flex-shrink:0;">
                        <input type="checkbox" id="remove_cover" name="remove_cover" value="1" class="tich-checkbox">
                        <label for="remove_cover">Remove current image</label>
                    </div>
                @endif
            </div>
            <div class="uf-field uf-field--check" style="grid-column: 1 / -1;">
                <input type="checkbox" id="is_featured" name="is_featured" value="1" class="tich-checkbox" @checked(old('is_featured', $activity->is_featured ?? false))>
                <label for="is_featured">Feature on Research portal</label>
            </div>
        </div>
    </div>
</div>

<div class="uf-form-section">
    <div class="uf-section-head">Schedule &amp; status</div>
    <div class="uf-section-body">
        <div class="uf-form-grid-2" data-research-schedule>
            <div class="uf-field">
                <label for="start_date">Start date <span class="uf-req">*</span></label>
                <input id="start_date" type="date" name="start_date" data-start-date required value="{{ old('start_date', optional($activity?->start_date)->format('Y-m-d')) }}">
            </div>
            <div class="uf-field" style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
                <div>
                    <label for="duration_value">Duration <span class="uf-req">*</span></label>
                    <input id="duration_value" type="number" name="duration_value" data-duration-value min="1" max="3650" required value="{{ old('duration_value', $activity->duration_value ?? 1) }}">
                </div>
                <div>
                    <label for="duration_unit">Unit <span class="uf-req">*</span></label>
                    <select id="duration_unit" name="duration_unit" data-duration-unit required>
                        @foreach ($durationUnits as $unit)
                            <option value="{{ $unit }}" @selected(old('duration_unit', $activity->duration_unit ?? 'months') === $unit)>{{ ucfirst($unit) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="uf-field">
                <label for="end_date">Expected completion <span class="uf-req">*</span></label>
                <input id="end_date" type="date" name="end_date" data-end-date required value="{{ old('end_date', optional($activity?->end_date)->format('Y-m-d')) }}">
                <span class="uf-hint">Auto-calculated from start + duration; you can override it.</span>
            </div>
            <div class="uf-field">
                <div class="uf-field--check">
                    <input type="checkbox" id="status_locked" name="status_locked" value="1" class="tich-checkbox" data-status-locked @checked($statusLocked)>
                    <label for="status_locked">Manual status override</label>
                </div>
                <select id="status" name="status" data-status-select class="tich-mt-2">
                    @foreach ($lifecycleStatuses as $st)
                        <option value="{{ $st }}" @selected(old('status', $activity->status ?? 'upcoming') === $st)>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
                <span class="uf-hint">When unchecked, status is Upcoming / Ongoing / Completed from dates.</span>
            </div>
        </div>
    </div>
</div>

<div class="uf-form-section">
    <div class="uf-section-head">Research description (CMS)</div>
    <div class="uf-section-body">
        <div class="uf-field">
            <label for="research_body">Full description</label>
            <div
                class="tich-cms-editor tich-cms-editor--locked-font"
                data-cms-editor
                data-upload-url="{{ $uploadUrl }}"
                data-input-id="research_body"
            >
                <div class="tich-cms-toolbar" role="toolbar" aria-label="Formatting">
                    <div class="tich-cms-toolbar__group">
                        <button type="button" data-cmd="undo" title="Undo">Undo</button>
                        <button type="button" data-cmd="redo" title="Redo">Redo</button>
                    </div>
                    <div class="tich-cms-toolbar__group">
                        <button type="button" data-cmd="bold" title="Bold"><strong>B</strong></button>
                        <button type="button" data-cmd="italic" title="Italic"><em>I</em></button>
                        <button type="button" data-cmd="underline" title="Underline"><u>U</u></button>
                    </div>
                    <div class="tich-cms-toolbar__group">
                        <select data-action="style" title="Styles">
                            <option value="">Styles</option>
                            <option value="p">Normal</option>
                            <option value="h2">Heading 2</option>
                            <option value="h3">Heading 3</option>
                            <option value="quote">Quote</option>
                        </select>
                        <button type="button" data-cmd="insertUnorderedList" title="Bullets">• List</button>
                        <button type="button" data-cmd="insertOrderedList" title="Numbered">1. List</button>
                        <button type="button" data-action="link" title="Insert link">Link</button>
                        <button type="button" data-action="image" title="Insert image">Image</button>
                        <button type="button" data-action="table" title="Insert table">Table</button>
                        <button type="button" data-cmd="removeFormat" title="Clear formatting">Clear</button>
                    </div>
                </div>
                <div
                    class="tich-cms-surface tich-prose"
                    contenteditable="true"
                    role="textbox"
                    aria-multiline="true"
                    aria-label="Research description editor"
                    data-cms-surface
                >{!! $bodyValue !!}</div>
                <textarea id="research_body" name="body" class="tich-cms-hidden-input">{{ $bodyValue }}</textarea>
                <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" data-cms-image-input hidden>
            </div>
            <span class="uf-hint">Same editor as blog posts — headings, lists, images, and tables.</span>
        </div>
        <div class="uf-field tich-mt-4">
            <label for="abstract">Abstract (plain)</label>
            <textarea id="abstract" name="abstract" rows="3" maxlength="20000">{{ old('abstract', $activity->abstract ?? '') }}</textarea>
        </div>
    </div>
</div>

<div class="uf-form-section">
    <div class="uf-section-head">Supporting documents</div>
    <div class="uf-section-body">
        @if ($activity && $activity->documents->isNotEmpty())
            <ul class="tich-mt-2" style="list-style:none;padding:0;margin:0 0 1rem;display:grid;gap:0.5rem;">
                @foreach ($activity->documents as $doc)
                    <li style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:center;justify-content:space-between;">
                        <span class="tich-text"><strong>{{ $doc->title }}</strong> <span class="tich-caption">({{ $doc->original_filename }})</span></span>
                        <button
                            type="submit"
                            class="uf-btn uf-btn-secondary"
                            form="delete-research-doc-{{ $doc->id }}"
                            onclick="return confirm('Remove this document?');"
                        >Remove</button>
                    </li>
                @endforeach
            </ul>
        @endif

        <div data-doc-rows style="display:grid;gap:0.75rem;">
            <div class="uf-form-grid-2" data-doc-row>
                <div class="uf-field">
                    <label>Document display name</label>
                    <input type="text" name="doc_titles[]" maxlength="300" placeholder="e.g. 2026 CHW Impact Survey">
                </div>
                <div class="uf-field">
                    <label>File</label>
                    <input type="file" name="doc_files[]" accept=".pdf,.doc,.docx,image/png,image/jpeg,image/webp">
                </div>
            </div>
        </div>
        <button type="button" class="uf-btn uf-btn-secondary tich-mt-4" data-add-doc-row>Add another document</button>
        <span class="uf-hint tich-mt-2" style="display:block;">Public visitors can open documents in a read-only viewer — downloads are blocked.</span>
    </div>
</div>

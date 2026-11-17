@props([
    'resource' => null,
])

<div class="uf-form-section">
    <div class="uf-section-head">Reference</div>
    <div class="uf-section-body">
        <div class="uf-form-grid-2">
            <div class="uf-field" style="grid-column: 1 / -1;">
                <label for="title">Title <span class="uf-req">*</span></label>
                <input id="title" type="text" name="title" value="{{ old('title', $resource->title ?? '') }}" required maxlength="500" placeholder="e.g. Effectiveness of Community Health Workers in Promotion of Maternal Health Services in Butere District, Rural Western Kenya">
            </div>
            <div class="uf-field" style="grid-column: 1 / -1;">
                <label for="authors">Authors</label>
                <input id="authors" type="text" name="authors" value="{{ old('authors', $resource->authors ?? '') }}" maxlength="500" placeholder="e.g. Caroline Akinyi, Jeremie Nzanzu, Dan Kaseje">
                <span class="uf-hint">Written as the publication prints them. The website shows "By" before this.</span>
            </div>
            <div class="uf-field">
                <label for="year">Year</label>
                <input id="year" type="text" name="year" value="{{ old('year', $resource->year ?? '') }}" maxlength="20" placeholder="e.g. 2015">
            </div>
            <div class="uf-field">
                <label for="publisher">Publisher</label>
                <input id="publisher" type="text" name="publisher" value="{{ old('publisher', $resource->publisher ?? '') }}" maxlength="300" placeholder="e.g. Horizon Research Publishing">
            </div>
            <div class="uf-field">
                <label for="isbn">ISBN</label>
                <input id="isbn" type="text" name="isbn" value="{{ old('isbn', $resource->isbn ?? '') }}" maxlength="150">
            </div>
            <div class="uf-field">
                <label for="doi">DOI</label>
                <input id="doi" type="text" name="doi" value="{{ old('doi', $resource->doi ?? '') }}" maxlength="300" placeholder="e.g. 10.13189/ujmsj.2015.03">
            </div>
        </div>
    </div>
</div>

<div class="uf-form-section">
    <div class="uf-section-head">Description</div>
    <div class="uf-section-body">
        <div class="uf-field">
            <label for="description">What the resource covers</label>
            <textarea id="description" name="description" rows="6" maxlength="20000" placeholder="Say in a few lines what the book or paper is about and who it helps.">{{ old('description', $resource->description ?? '') }}</textarea>
        </div>
    </div>
</div>

<div class="uf-form-section">
    <div class="uf-section-head">Document</div>
    <div class="uf-section-body">
        <div class="uf-field">
            <label for="file">File to download</label>
            <input id="file" type="file" name="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.csv,.txt,.zip,.epub">
            <span class="uf-hint">PDF, Word, Excel, PowerPoint, EPUB or ZIP, up to 20 MB. Visitors get a Download button that keeps the file's own name.</span>
            @if ($resource?->hasFile())
                <div class="uf-field--check tich-mt-2" style="flex-wrap:nowrap;">
                    <span class="tich-text tich-text--sm" style="flex:1;min-width:0;word-break:break-all;">{{ $resource->original_name ?: 'Document on record' }}</span>
                    <input type="checkbox" id="remove_file" name="remove_file" value="1" class="tich-checkbox">
                    <label for="remove_file">Remove current file</label>
                </div>
            @endif
        </div>
        <div class="uf-field uf-field--check tich-mt-3">
            <input type="checkbox" id="is_active" name="is_active" value="1" class="tich-checkbox" @checked(old('is_active', $resource->is_active ?? true))>
            <label for="is_active">Show on the website</label>
            <span class="uf-hint">Shown to visitors under "Research resources" on the public Research page.</span>
        </div>
    </div>
</div>

@props(['post' => null, 'statuses' => [], 'uploadUrl' => null])

@php
    $bodyValue = old('body', $post->body ?? '');
@endphp

<div class="tich-blog-compose__meta tich-card">
    <div class="tich-form-grid tich-form-grid--2">
        <div class="tich-form-group" style="grid-column:1/-1;">
            <label class="tich-label" for="blog_title">Title</label>
            <input id="blog_title" type="text" name="title" class="tich-input" value="{{ old('title', $post->title ?? '') }}" required maxlength="300">
        </div>
        <div class="tich-form-group" style="grid-column:1/-1;">
            <label class="tich-label" for="blog_subtitle">Subtitle</label>
            <input id="blog_subtitle" type="text" name="subtitle" class="tich-input" value="{{ old('subtitle', $post->subtitle ?? '') }}" maxlength="500">
        </div>
        <div class="tich-form-group" style="grid-column:1/-1;">
            <label class="tich-label" for="blog_excerpt">Excerpt</label>
            <textarea id="blog_excerpt" name="excerpt" class="tich-input" rows="2" maxlength="500">{{ old('excerpt', $post->excerpt ?? '') }}</textarea>
        </div>
        <div class="tich-form-group">
            <label class="tich-label" for="blog_status">Status</label>
            <select id="blog_status" name="status" class="tich-input" required>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(old('status', $post->status ?? 'draft') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="tich-form-group">
            <label class="tich-label" for="blog_featured_image">Featured image</label>
            <input id="blog_featured_image" type="file" name="featured_image" class="tich-input" accept="image/jpeg,image/png,image/webp,image/gif">
            <p class="tich-caption tich-mt-2">Cover image for cards and article header. Stored as WebP.</p>
            @if ($post?->imageUrl())
                <div class="tich-mt-2" style="display:flex;gap:0.75rem;align-items:center;">
                    <img src="{{ $post->imageUrl() }}" alt="" class="tich-program-admin-thumb" style="width:4.5rem;height:3rem;object-fit:cover;">
                    <label style="display:flex;gap:0.5rem;align-items:center;">
                        <input type="checkbox" name="remove_image" value="1">
                        <span class="tich-text">Remove current image</span>
                    </label>
                </div>
            @endif
        </div>
        <div class="tich-form-group">
            <label class="tich-label" for="blog_seo_title">SEO title</label>
            <input id="blog_seo_title" type="text" name="seo_meta_title" class="tich-input" maxlength="300" value="{{ old('seo_meta_title', $post->seo_meta_title ?? '') }}" placeholder="Optional — defaults to post title">
        </div>
        <div class="tich-form-group">
            <label class="tich-label" for="blog_seo_description">SEO description</label>
            <textarea id="blog_seo_description" name="seo_meta_description" class="tich-input" rows="2" maxlength="500" placeholder="Optional — shown in search results">{{ old('seo_meta_description', $post->seo_meta_description ?? '') }}</textarea>
        </div>
    </div>
</div>

<div class="tich-form-group tich-mt-6">
    <label class="tich-label" for="blog_body">Article body</label>
    <div
        class="tich-cms-editor"
        data-cms-editor
        data-upload-url="{{ $uploadUrl }}"
        data-input-id="blog_body"
    >
        <div class="tich-cms-toolbar" role="toolbar" aria-label="Formatting">
            <div class="tich-cms-toolbar__group">
                <button type="button" data-cmd="undo" title="Undo">Undo</button>
                <button type="button" data-cmd="redo" title="Redo">Redo</button>
            </div>
            <div class="tich-cms-toolbar__group">
                <select data-cmd="fontName" title="Font">
                    <option value="">Font</option>
                    <option value="Merriweather">Merriweather</option>
                    <option value="Arial">Arial</option>
                    <option value="Georgia">Georgia</option>
                    <option value="Times New Roman">Times New Roman</option>
                    <option value="Calibri">Calibri</option>
                    <option value="Verdana">Verdana</option>
                    <option value="Courier New">Courier New</option>
                </select>
                <select data-cmd="fontSize" title="Font size">
                    <option value="">Size</option>
                    <option value="1">8</option>
                    <option value="2">10</option>
                    <option value="3">12</option>
                    <option value="4">14</option>
                    <option value="5">18</option>
                    <option value="6">24</option>
                    <option value="7">36</option>
                </select>
            </div>
            <div class="tich-cms-toolbar__group">
                <button type="button" data-cmd="bold" title="Bold"><strong>B</strong></button>
                <button type="button" data-cmd="italic" title="Italic"><em>I</em></button>
                <button type="button" data-cmd="underline" title="Underline"><u>U</u></button>
                <button type="button" data-cmd="strikeThrough" title="Strikethrough"><s>S</s></button>
                <button type="button" data-cmd="superscript" title="Superscript">X²</button>
                <button type="button" data-cmd="subscript" title="Subscript">X₂</button>
            </div>
            <div class="tich-cms-toolbar__group">
                <label class="tich-cms-toolbar__swatch" title="Font colour">
                    A
                    <input type="color" data-cmd="foreColor" value="#494c50">
                </label>
                <label class="tich-cms-toolbar__swatch" title="Highlight">
                    ▮
                    <input type="color" data-cmd="hiliteColor" value="#fff59d">
                </label>
                <button type="button" data-action="uppercase" title="UPPERCASE">AA</button>
                <button type="button" data-action="lowercase" title="lowercase">aa</button>
                <button type="button" data-action="titlecase" title="Title Case">Aa</button>
            </div>
            <div class="tich-cms-toolbar__group">
                <select data-action="style" title="Styles">
                    <option value="">Styles</option>
                    <option value="p">Normal</option>
                    <option value="title">Title</option>
                    <option value="h1">Heading 1</option>
                    <option value="h2">Heading 2</option>
                    <option value="h3">Heading 3</option>
                    <option value="h4">Heading 4</option>
                    <option value="h5">Heading 5</option>
                    <option value="quote">Quote</option>
                    <option value="intense-quote">Intense quote</option>
                </select>
            </div>
            <div class="tich-cms-toolbar__group">
                <button type="button" data-cmd="justifyLeft" title="Align left">Left</button>
                <button type="button" data-cmd="justifyCenter" title="Align center">Center</button>
                <button type="button" data-cmd="justifyRight" title="Align right">Right</button>
                <button type="button" data-cmd="justifyFull" title="Justify">Justify</button>
                <button type="button" data-cmd="insertUnorderedList" title="Bullets">• List</button>
                <button type="button" data-cmd="insertOrderedList" title="Numbered">1. List</button>
                <button type="button" data-cmd="outdent" title="Decrease indent">⇤</button>
                <button type="button" data-cmd="indent" title="Increase indent">⇥</button>
            </div>
            <div class="tich-cms-toolbar__group">
                <button type="button" data-action="link" title="Insert link">Link</button>
                <button type="button" data-action="image" title="Insert image">Image</button>
                <button type="button" data-action="table" title="Insert table">Table</button>
                <select data-action="shape" title="Insert shape">
                    <option value="">Shapes</option>
                    <option value="circle">Circle</option>
                    <option value="square">Square</option>
                    <option value="rounded">Rounded</option>
                    <option value="triangle">Triangle</option>
                    <option value="diamond">Diamond</option>
                    <option value="star">Star</option>
                    <option value="arrow">Arrow</option>
                    <option value="line">Line</option>
                </select>
                <button type="button" data-action="hr" title="Horizontal line">—</button>
                <button type="button" data-action="find" title="Find and replace">Find</button>
                <button type="button" data-cmd="removeFormat" title="Clear formatting">Clear</button>
            </div>
        </div>

        <div
            class="tich-cms-surface tich-prose"
            contenteditable="true"
            role="textbox"
            aria-multiline="true"
            aria-label="Article body editor"
            data-cms-surface
        >{!! $bodyValue !!}</div>

        <textarea id="blog_body" name="body" class="tich-cms-hidden-input" required>{{ $bodyValue }}</textarea>
        <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" data-cms-image-input hidden>
    </div>
    <p class="tich-caption tich-mt-2">Use the toolbar like Microsoft Word. Images uploaded into the body are saved to the media library.</p>
</div>

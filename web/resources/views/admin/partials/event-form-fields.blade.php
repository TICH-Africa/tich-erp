@props([
    'event' => null,
    'eventTypes' => [],
    'fieldIdPrefix' => '',
])

<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}cover_image" @endif>Cover image</label>
    @if ($event?->coverImageUrl())
        <img
            src="{{ $event->coverImageUrl() }}"
            alt="{{ $event->title }}"
            id="{{ $fieldIdPrefix }}cover_preview"
            style="display:block;width:100%;max-width:16rem;height:8rem;object-fit:cover;border-radius:var(--radius-md,0.5rem);margin-bottom:0.75rem;"
        >
    @else
        <img
            src=""
            alt=""
            id="{{ $fieldIdPrefix }}cover_preview"
            hidden
            style="width:100%;max-width:16rem;height:8rem;object-fit:cover;border-radius:var(--radius-md,0.5rem);margin-bottom:0.75rem;"
        >
    @endif
    <input
        type="file"
        name="cover_image"
        @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}cover_image" @endif
        class="tich-input"
        accept="image/jpeg,image/png,image/webp,image/gif"
    >
    <p class="tich-caption tich-mt-2">Upload a file only (JPG, PNG, GIF, or WebP) - not a link. Compressed and saved as WebP. Used on the events feed and featured hero slide.</p>
</div>

<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}title" @endif>Title</label>
    <input type="text" name="title" @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}title" @endif class="tich-input" value="{{ old('title', $event->title ?? '') }}" required>
</div>

<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}subtitle" @endif>Subtitle</label>
    <input type="text" name="subtitle" @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}subtitle" @endif class="tich-input" value="{{ old('subtitle', $event->subtitle ?? '') }}">
</div>

<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}event_type" @endif>Type</label>
    <select name="event_type" @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}event_type" @endif class="tich-input" required>
        @foreach ($eventTypes as $type)
            <option value="{{ $type }}" @selected(old('event_type', $event->event_type ?? 'conference') === $type)>
                {{ ucfirst(str_replace('_', ' ', $type)) }}
            </option>
        @endforeach
    </select>
</div>

<div class="tich-form-group">
    <x-cms-basic-editor
        name="description"
        :id="($fieldIdPrefix ?? '').'description'"
        label="Description"
        :value="old('description', $event->description ?? '')"
        min-height="7rem"
    />
</div>

<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}start_datetime" @endif>Starts</label>
    <input
        type="datetime-local"
        name="start_datetime"
        @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}start_datetime" @endif
        class="tich-input"
        value="{{ old('start_datetime', isset($event) && $event->start_datetime ? $event->start_datetime->format('Y-m-d\\TH:i') : '') }}"
        required
    >
</div>

<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}end_datetime" @endif>Ends</label>
    <input
        type="datetime-local"
        name="end_datetime"
        @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}end_datetime" @endif
        class="tich-input"
        value="{{ old('end_datetime', isset($event) && $event->end_datetime ? $event->end_datetime->format('Y-m-d\\TH:i') : '') }}"
    >
</div>

<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}venue" @endif>Venue</label>
    <input type="text" name="venue" @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}venue" @endif class="tich-input" value="{{ old('venue', $event->venue ?? '') }}">
</div>

<div class="tich-form-group">
    <label class="tich-label" @if ($fieldIdPrefix) for="{{ $fieldIdPrefix }}registration_url_or_form" @endif>Registration URL</label>
    <input type="text" name="registration_url_or_form" @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}registration_url_or_form" @endif class="tich-input" value="{{ old('registration_url_or_form', $event->registration_url_or_form ?? '') }}" placeholder="/events or https://…">
</div>

<label style="display:flex;gap:0.5rem;align-items:center;margin-bottom:0.75rem;">
    <input type="checkbox" name="is_public" value="1" @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}is_public" @endif @checked(old('is_public', $event->is_public ?? true))>
    <span class="tich-text">Public (show on website)</span>
</label>

<label style="display:flex;gap:0.5rem;align-items:center;">
    <input type="checkbox" name="is_featured" value="1" @if ($fieldIdPrefix) id="{{ $fieldIdPrefix }}is_featured" @endif @checked(old('is_featured', $event->is_featured ?? false))>
    <span class="tich-text">Featured on homepage hero</span>
</label>
<p class="tich-caption tich-mt-2">Featured public events appear as slides in the homepage hero carousel.</p>

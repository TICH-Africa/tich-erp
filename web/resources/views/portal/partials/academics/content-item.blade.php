{{-- One posted note on a unit. Expects: $item, $number --}}
<div class="tich-lesson-item tich-mb-4">
    <div class="tich-lesson-item__header">
        <div>
            <p class="tich-caption" style="margin:0;">Lesson {{ $number }}</p>
            <strong>{{ $item->title }}</strong>
            <div class="tich-caption tich-mt-1">
                {{ ucfirst(str_replace('_', ' ', $item->content_type)) }}
                <span class="tich-badge tich-badge--success" style="margin-left:0.5rem;">Published</span>
                <span style="margin-left:0.5rem;">{{ ($item->published_at ?? $item->created_at)?->format('d M Y') }}</span>
                @if ($item->available_from || $item->available_until)
                    <span style="margin-left:0.5rem;">
                        @if ($item->available_from)
                            From {{ $item->available_from->format('d M Y') }}
                        @endif
                        @if ($item->available_until)
                            Until {{ $item->available_until->format('d M Y') }}
                        @endif
                    </span>
                @endif
            </div>
        </div>
    </div>

    @if ($item->content_text)
        <div class="tich-lesson-item__body tich-mt-2">
            <p class="tich-text" style="margin:0;">{{ $item->content_text }}</p>
        </div>
    @endif

    @if ($item->file_path)
        <div class="tich-mt-2">
            <a href="{{ asset('storage/' . $item->file_path) }}" target="_blank" class="tich-btn tich-btn-secondary" style="font-size:0.875rem; padding:0.35rem 0.75rem;">
                Download {{ $item->original_filename ?: 'File' }}
            </a>
            <span class="tich-caption tich-ml-2">{{ $item->mime_type }} · {{ number_format($item->file_size / 1024, 1) }} KB</span>
        </div>
    @endif

    @if ($item->external_url)
        <div class="tich-mt-2">
            <a href="{{ $item->external_url }}" target="_blank" class="tich-btn tich-btn-secondary" style="font-size:0.875rem; padding:0.35rem 0.75rem;">
                Open link
            </a>
        </div>
    @endif
</div>

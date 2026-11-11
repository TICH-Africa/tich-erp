{{--
    Posted notes arranged as numbered lessons under section headers.
    Expects: $sections (label => items), $rowMode ('link' or 'actions'),
             $openRoute, $viewRoute, $downloadRoute
--}}
@php
    $rowMode = $rowMode ?? 'link';
    $lessonCount = $sections->sum(fn ($items) => $items->count());
@endphp

<div class="tich-module-list">
    @foreach ($sections as $sectionLabel => $items)
        <details class="tich-module" @if($loop->first) open @endif>
            <summary class="tich-module__head">
                <span class="tich-module__badge" aria-hidden="true"></span>
                <span class="tich-module__heading">
                    <span class="tich-module__title">{{ $sectionLabel }}</span>
                    <span class="tich-module__count">{{ $items->count() }} {{ Str::plural('lesson', $items->count()) }}</span>
                </span>
            </summary>

            <ol class="tich-module__lessons">
                @foreach ($items as $item)
                    <li class="tich-module__lesson">
                        <span class="tich-module__number">{{ $item->lesson_number ?: '–' }}</span>
                        <span class="tich-module__body">
                            <span class="tich-module__name">{{ $item->title }}</span>
                            <span class="tich-module__meta">
                                @if ($item->file_path)
                                    {{ $item->original_filename ?: 'file' }}
                                    @if ($item->file_size)
                                        · {{ number_format($item->file_size / 1024, 0) }} KB
                                    @endif
                                @else
                                    Notes only
                                @endif
                                · {{ ($item->published_at ?? $item->created_at)?->format('d M Y') }}
                            </span>
                        </span>

                        @if ($rowMode === 'link')
                            <span class="tich-module__actions">
                                <a href="{{ route($openRoute, $item) }}" class="tich-btn tich-btn-primary tich-btn-sm">Open</a>
                                @if ($item->file_path)
                                    <a href="{{ route($downloadRoute, $item) }}" class="tich-btn tich-btn-ghost tich-btn-sm">Download</a>
                                @endif
                            </span>
                        @else
                            <span class="tich-module__actions">
                                @if ($item->file_path)
                                    <a href="{{ route($viewRoute, $item) }}" target="_blank" rel="noopener" class="tich-btn tich-btn-secondary tich-btn-sm">View</a>
                                    <a href="{{ route($downloadRoute, $item) }}" class="tich-btn tich-btn-ghost tich-btn-sm">Download</a>
                                @else
                                    <span class="tich-caption">Posted</span>
                                @endif
                            </span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </details>
    @endforeach

    <p class="tich-caption tich-mt-4">{{ $lessonCount }} {{ Str::plural('lesson', $lessonCount) }} in {{ $sections->count() }} {{ Str::plural('section', $sections->count()) }}</p>
</div>

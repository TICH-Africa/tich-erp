@extends('layouts.portal')

@section('portal-content')
    @php
        $backUrl = route('portal.dashboard', ['section' => 'academics', 'tab' => 'content', 'unit' => $content->unit_id]);
    @endphp

    <x-page-toolbar
        title="{{ $content->lessonLabel() }}: {{ $content->title }}"
        meta="{{ $unit ? $unit->unit_code.' - '.$unit->unit_name : 'Learning content' }} · {{ $content->sectionLabel() }}"
    >
        <x-slot:actions>
            <a href="{{ $backUrl }}" class="tich-btn tich-btn-ghost">← All lessons</a>
            @if ($downloadUrl)
                <a href="{{ $downloadUrl }}" class="tich-btn tich-btn-secondary">Download</a>
            @endif
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-lesson-read tich-mt-6">
        <article class="tich-lesson-read__main">
            @if ($content->content_text)
                <div class="tich-card">
                    <h2 class="tich-h4">Lecturer's notes</h2>
                    <p class="tich-text tich-mt-2" style="white-space: pre-line;">{{ $content->content_text }}</p>
                </div>
            @endif

            <div class="tich-card tich-lesson-read__file">
                @if ($previewKind === 'pdf' && $fileUrl)
                    <iframe src="{{ $fileUrl }}" class="tich-lesson-read__frame" title="{{ $content->original_filename ?: $content->title }}"></iframe>
                @elseif ($previewKind === 'image' && $fileUrl)
                    <img src="{{ $fileUrl }}" alt="{{ $content->title }}" class="tich-lesson-read__image">
                @elseif ($previewHtml)
                    <div class="tich-lesson-read__doc">{!! $previewHtml !!}</div>
                @else
                    <div class="tich-lesson-read__empty">
                        <p class="tich-text">{{ $previewReason ?: 'This lesson has no file to show.' }}</p>
                        @if ($downloadUrl)
                            <a href="{{ $downloadUrl }}" class="tich-btn tich-btn-primary tich-mt-4">Download {{ $content->original_filename ?: 'file' }}</a>
                        @endif
                    </div>
                @endif

                @if ($downloadUrl && ($previewKind === 'pdf' || $previewKind === 'image' || $previewHtml))
                    <div class="tich-lesson-read__file-actions">
                        <a href="{{ $fileUrl }}" target="_blank" rel="noopener" class="tich-btn tich-btn-secondary tich-btn-sm">Open file full size</a>
                        <a href="{{ $downloadUrl }}" class="tich-btn tich-btn-ghost tich-btn-sm">Download</a>
                        @if ($content->file_size)
                            <span class="tich-caption">{{ number_format($content->file_size / 1024, 0) }} KB</span>
                        @endif
                    </div>
                @endif
            </div>

            <nav class="tich-lesson-read__pager" aria-label="Lesson navigation">
                @if ($previous)
                    <a href="{{ route('portal.learning-content.read', $previous) }}" class="tich-btn tich-btn-ghost">← {{ $previous->lessonLabel() }}: {{ $previous->title }}</a>
                @else
                    <span class="tich-caption">First lesson</span>
                @endif

                <span class="tich-caption">Lesson {{ $position + 1 }} of {{ $lessons->count() }}</span>

                @if ($next)
                    <a href="{{ route('portal.learning-content.read', $next) }}" class="tich-btn tich-btn-primary">Next: {{ $next->lessonLabel() }}: {{ $next->title }} →</a>
                @else
                    <span class="tich-caption">Last lesson</span>
                @endif
            </nav>
        </article>

        <aside class="tich-lesson-read__side">
            <h2 class="tich-h4">Lessons on this unit</h2>
            <ol class="tich-lesson-read__index">
                @foreach ($lessons as $row)
                    <li class="{{ $row->id === $content->id ? 'is-current' : '' }}">
                        <a href="{{ route('portal.learning-content.read', $row) }}">
                            <span class="tich-lesson-read__index-number">{{ $row->lesson_number ?: '–' }}</span>
                            <span>{{ $row->title }}</span>
                        </a>
                    </li>
                @endforeach
            </ol>
        </aside>
    </div>
@endsection

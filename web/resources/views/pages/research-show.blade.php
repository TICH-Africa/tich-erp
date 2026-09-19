@extends('layouts.app')

@section('title', $activity->title)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($activity->summary ?: $activity->subtitle ?: $activity->title), 160, ''))

@section('content')
    <x-animated-section animation="top">
        <article class="tich-section tich-article">
            <div class="tich-container" style="max-width: 52rem;">
                <p class="tich-caption tich-article-back">
                    <a href="{{ route('research') }}" class="tich-link">← Back to Research</a>
                </p>

                <header class="tich-mt-6">
                    <p class="tich-caption">
                        {{ $activity->statusLabel() }}
                        @if ($activity->start_date)
                            · {{ $activity->start_date->format('d M Y') }}
                            @if ($activity->end_date)
                                – {{ $activity->end_date->format('d M Y') }}
                            @endif
                        @endif
                        @if ($activity->is_featured) · Featured @endif
                    </p>
                    <h1 class="tich-h1 tich-mt-2">{{ $activity->title }}</h1>
                    @if ($activity->subtitle)
                        <p class="tich-text tich-mt-2">{{ $activity->subtitle }}</p>
                    @endif
                </header>

                @if ($activity->coverUrl())
                    <figure class="tich-mt-8">
                        <img
                            src="{{ $activity->coverUrl() }}"
                            alt="{{ $activity->title }}"
                            style="width: 100%; height: auto; max-height: 26rem; object-fit: cover; border-radius: var(--radius-md, 0.5rem);"
                        >
                    </figure>
                @endif

                @if ($activity->summary)
                    <p class="tich-text tich-mt-8" style="font-size:1.05rem;">{{ $activity->summary }}</p>
                @endif

                @if ($activity->body)
                    <div class="tich-prose-article tich-mt-8">
                        {!! $activity->body !!}
                    </div>
                @elseif ($activity->abstract)
                    <div class="tich-prose-article tich-mt-8">
                        <p>{{ $activity->abstract }}</p>
                    </div>
                @endif

                @if ($activity->documents->isNotEmpty())
                    <section class="tich-mt-10" aria-labelledby="research-docs-heading">
                        <h2 id="research-docs-heading" class="tich-h2">Documents</h2>
                        <p class="tich-caption tich-mt-2">Open a document to read it online. Downloads are not permitted.</p>
                        <ul class="tich-mt-4" style="list-style:none;padding:0;margin:0;display:grid;gap:0.75rem;">
                            @foreach ($activity->documents as $doc)
                                <li>
                                    <a
                                        href="{{ route('research.documents.view', $doc) }}"
                                        class="tich-link"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >{{ $doc->title }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>
        </article>
    </x-animated-section>
@endsection

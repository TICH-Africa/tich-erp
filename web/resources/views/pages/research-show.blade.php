@extends('layouts.app')

@section('title', $activity->title)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($activity->summary ?: $activity->subtitle ?: $activity->title), 160, ''))

@section('content')
    <article class="research-public-show">
        <div class="research-public-show__inner">
            <p class="research-public-show__back">
                <a href="{{ route('research') }}">← Back to Research</a>
            </p>

            <header class="research-public-show__header">
                <p class="research-public-show__meta">
                    <span class="research-public-show__status">{{ $activity->statusLabel() }}</span>
                    @if ($activity->start_date)
                        <span>· {{ $activity->start_date->format('d M Y') }}
                        @if ($activity->end_date)
                            – {{ $activity->end_date->format('d M Y') }}
                        @endif
                        </span>
                    @endif
                    @if ($activity->is_featured)
                        <span>· Featured</span>
                    @endif
                </p>
                <h1 class="research-public-show__title">{{ $activity->title }}</h1>
                @if ($activity->subtitle)
                    <p class="research-public-show__subtitle">{{ $activity->subtitle }}</p>
                @endif
            </header>

            @if ($activity->coverUrl())
                <figure class="research-public-show__cover">
                    <img src="{{ $activity->coverUrl() }}" alt="{{ $activity->title }}">
                </figure>
            @endif

            <div class="research-public-show__layout">
                <div class="research-public-show__main">
                    @if ($activity->summary)
                        <p class="research-public-show__summary">{{ $activity->summary }}</p>
                    @endif

                    @if ($activity->body)
                        <div class="research-public-show__body tich-prose-article research-public-show__body--locked-font">
                            {!! $activity->body !!}
                        </div>
                    @elseif ($activity->abstract)
                        <div class="research-public-show__body">
                            <p>{{ $activity->abstract }}</p>
                        </div>
                    @endif
                </div>

                <aside class="research-public-show__aside">
                    <h2 class="research-public-show__aside-title">Timeline</h2>
                    <dl class="research-public-show__facts">
                        <div>
                            <dt>Start</dt>
                            <dd>{{ $activity->start_date?->format('d M Y') ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt>Duration</dt>
                            <dd>@if($activity->duration_value){{ $activity->duration_value }} {{ $activity->duration_unit }}@else — @endif</dd>
                        </div>
                        <div>
                            <dt>Expected completion</dt>
                            <dd>{{ $activity->end_date?->format('d M Y') ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt>Status</dt>
                            <dd>{{ $activity->statusLabel() }}</dd>
                        </div>
                    </dl>

                    @if ($activity->documents->isNotEmpty())
                        <h2 class="research-public-show__aside-title">Documents</h2>
                        <p class="research-public-show__doc-note">Read online only — downloads are not available.</p>
                        <ul class="research-public-show__docs">
                            @foreach ($activity->documents as $doc)
                                <li>
                                    <a
                                        href="{{ route('research.documents.view', $doc) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >{{ $doc->title }}</a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </aside>
            </div>
        </div>
    </article>
@endsection

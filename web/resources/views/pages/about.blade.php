@extends('layouts.app')

@section('title', 'About Us')

@section('content')
    <section class="tich-section tich-about-page">
        <div class="tich-container">
            <header class="tich-section__intro tich-about-page__intro tich-mb-8" data-about-reveal>
                <h1 class="tich-h1">About Us</h1>
                <p class="tich-text tich-mt-2">Who we are, what drives us, and how we got here.</p>
            </header>

            @php $imageIndex = 0; @endphp
            @forelse ($blocks as $block)
                @php
                    $imageUrl = $block->imageUrl();
                    $imageOnLeft = $imageUrl && ($imageIndex % 2 === 1);
                    if ($imageUrl) {
                        $imageIndex++;
                    }
                @endphp
                <article
                    @class([
                        'tich-about-block',
                        'tich-about-block--media-left' => $imageOnLeft,
                        'tich-about-block--media-right' => $imageUrl && ! $imageOnLeft,
                        'tich-about-block--text-only' => ! $imageUrl,
                    ])
                    id="{{ $block->block_key }}"
                    data-about-reveal
                >
                    <div class="tich-about-block__grid tich-grid tich-grid--2">
                        <div class="tich-about-block__copy" data-about-slide="inward-start">
                            <h2 class="tich-h2">{{ $block->title }}</h2>
                            @if ($block->subtitle)
                                <p class="tich-caption tich-mt-2">{{ $block->subtitle }}</p>
                            @endif
                            <div class="tich-text tich-mt-4" style="white-space: pre-wrap;">{{ $block->body }}</div>
                        </div>
                        @if ($imageUrl)
                            <div class="tich-about-block__media" data-about-slide="inward-end">
                                <img
                                    src="{{ $imageUrl }}"
                                    alt="{{ $block->title }}"
                                    class="tich-about-block__image"
                                >
                            </div>
                        @endif
                    </div>
                </article>
            @empty
                <p class="tich-text">About content will appear here once ICT publishes the About Us sections.</p>
            @endforelse
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/tich-about-reveal.js') }}" defer></script>
@endpush

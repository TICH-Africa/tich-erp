@extends('layouts.app')

@section('title', $page->seo_meta_title ?? $page->title)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($page->seo_meta_description ?? $page->body ?? $page->title), 160, ''))

@section('content')
    <x-animated-section animation="top">
        <article class="tich-section tich-article">
            <div class="tich-container" style="max-width: 48rem;">
                <header>
                    <h1 class="tich-h1">{{ $page->title }}</h1>
                    @if (!empty($page->updated_at))
                        <p class="tich-caption tich-mt-2">Last updated {{ $page->updated_at->format('j F Y') }}</p>
                    @endif
                </header>

                <div class="tich-prose tich-mt-8">
                    {!! $page->body !!}
                </div>

                <p class="tich-caption tich-mt-10">
                    @if (($page->slug ?? '') === 'privacy')
                        Also see our <a href="{{ route('terms') }}" class="tich-link">Terms and Conditions</a>.
                    @else
                        Also see our <a href="{{ route('privacy') }}" class="tich-link">Privacy Policy</a>.
                    @endif
                </p>
            </div>
        </article>
    </x-animated-section>
@endsection

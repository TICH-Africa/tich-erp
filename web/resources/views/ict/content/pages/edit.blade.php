@extends('layouts.ict')

@section('title', 'Edit ' . $page->title)

@section('ict-content')
    <x-page-toolbar title="Edit {{ $page->title }}" meta="Public URL: /{{ $page->slug }}">
        <x-slot:actions>
            @if ($page->isPublished())
                <a href="{{ $page->slug === 'privacy' ? route('privacy') : route('terms') }}" class="tich-btn tich-btn-secondary" target="_blank" rel="noopener">View live</a>
            @endif
            <a href="{{ route('ict.content.pages.index') }}" class="tich-btn tich-btn-secondary">Back to list</a>
        </x-slot:actions>
    </x-page-toolbar>

    <form method="POST" action="{{ route('ict.content.pages.update', $page) }}" class="tich-blog-compose" data-blog-compose>
        @csrf
        @method('PUT')
        @include('ict.content.pages._form', [
            'page' => $page,
            'statuses' => $statuses,
            'uploadUrl' => route('ict.content.pages.upload-image'),
        ])
        <div class="tich-blog-compose__footer">
            <a href="{{ route('ict.content.pages.index') }}" class="tich-btn tich-btn-secondary">Cancel</a>
            <button type="submit" class="tich-btn tich-btn-primary">Update page</button>
        </div>
    </form>
@endsection

@section('scripts')
    @parent
    <x-asset.script path="js/tich-cms-editor.js" :defer="false" />
@endsection

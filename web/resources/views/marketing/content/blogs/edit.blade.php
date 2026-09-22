@extends('layouts.marketing')

@section('title', 'Edit blog post')

@section('department-content')
    <x-page-toolbar title="Edit blog post" meta="{{ $post->title }}">
        <x-slot:actions>
            @if ($post->status === 'published')
                <a href="{{ route('blog.show', $post->slug) }}" class="tich-btn tich-btn-secondary" target="_blank" rel="noopener">View live</a>
            @endif
            <a href="{{ route('marketing.blogs.index') }}" class="tich-btn tich-btn-secondary">Back to list</a>
        </x-slot:actions>
    </x-page-toolbar>

    <form method="POST" action="{{ route('marketing.blogs.update', $post) }}" enctype="multipart/form-data" class="tich-blog-compose" data-blog-compose>
        @csrf
        @method('PUT')
        @include('marketing.content.blogs._form', [
            'post' => $post,
            'statuses' => $statuses,
            'uploadUrl' => route('marketing.blogs.upload-image'),
        ])
        <div class="tich-blog-compose__footer">
            <a href="{{ route('marketing.blogs.index') }}" class="tich-btn tich-btn-secondary">Cancel</a>
            <button type="submit" class="tich-btn tich-btn-primary">Update post</button>
        </div>
    </form>
@endsection

@section('scripts')
    @parent
    <x-asset.script path="js/tich-cms-editor.js" :defer="false" />
@endsection

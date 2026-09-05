@extends('layouts.ict')

@section('title', 'Add blog post')

@section('ict-content')
    <x-page-toolbar title="Add blog post" meta="Write and format the article in the editor">
        <x-slot:actions>
            <a href="{{ route('ict.content.blogs.index') }}" class="tich-btn tich-btn-secondary">Back to list</a>
        </x-slot:actions>
    </x-page-toolbar>

    <form method="POST" action="{{ route('ict.content.blogs.store') }}" enctype="multipart/form-data" class="tich-blog-compose" data-blog-compose>
        @csrf
        @include('ict.content.blogs._form', [
            'statuses' => $statuses,
            'uploadUrl' => route('ict.content.blogs.upload-image'),
        ])
        <div class="tich-blog-compose__footer">
            <a href="{{ route('ict.content.blogs.index') }}" class="tich-btn tich-btn-secondary">Cancel</a>
            <button type="submit" class="tich-btn tich-btn-primary">Save post</button>
        </div>
    </form>
@endsection

@section('scripts')
    @parent
    <x-asset.script path="js/tich-cms-editor.js" :defer="false" />
@endsection

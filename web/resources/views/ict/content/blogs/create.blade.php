@extends('layouts.ict')

@section('title', 'Add blog post')

@section('ict-content')
    <x-page-toolbar title="Add blog post" meta="Write and format the article in the editor">
        <x-slot:actions>
            <a href="{{ route('ict.content.blogs.index') }}" class="tich-btn tich-btn-secondary">Back to list</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="uf-form">
        <form method="POST" action="{{ route('ict.content.blogs.store') }}" enctype="multipart/form-data" class="tich-blog-compose" data-blog-compose data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">ICT · Content</div>
                    <div class="uf-amount-bar__sum">Add blog post</div>
                </div>
                <span class="uf-badge">Compose</span>
            </div>

            @include('ict.content.blogs._form', [
                'statuses' => $statuses,
                'uploadUrl' => route('ict.content.blogs.upload-image'),
            ])

            <div class="uf-form-section">
                <div class="uf-section-body">
                    <div class="uf-form-actions tich-blog-compose__footer">
                        <a href="{{ route('ict.content.blogs.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                        <button type="submit" class="uf-btn uf-btn-primary">Save post</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    @parent
    <x-asset.script path="js/tich-cms-editor.js" :defer="false" />
@endsection

@extends('layouts.research')

@section('title', 'Edit research resource')

@section('research-content')
    <x-page-toolbar title="Edit research resource" meta="{{ $resource->title }}">
        <x-slot:actions>
            <a href="{{ route('research.resources.index') }}" class="tich-btn tich-btn-secondary">Back to list</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if ($errors->any())
        <div class="tich-alert tich-alert--error tich-mt-4">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <div class="uf-form">
        <form method="POST" action="{{ route('research.resources.update', $resource) }}" enctype="multipart/form-data" data-uf="ready">
            @csrf
            @method('PUT')

            @include('research.resources._form', ['resource' => $resource])

            <div class="uf-form-section">
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <a href="{{ route('research.resources.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                        <button type="submit" class="uf-btn uf-btn-primary">Save resource</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

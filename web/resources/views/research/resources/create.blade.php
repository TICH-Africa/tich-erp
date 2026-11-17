@extends('layouts.research')

@section('title', 'Add research resource')

@section('research-content')
    <x-page-toolbar title="Add research resource" meta="The reference, a description and the file visitors download">
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
        <form method="POST" action="{{ route('research.resources.store') }}" enctype="multipart/form-data" data-uf="ready">
            @csrf

            @include('research.resources._form')

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

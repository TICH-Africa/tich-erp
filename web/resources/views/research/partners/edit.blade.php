@extends('layouts.research')

@section('title', 'Edit partner')

@section('research-content')
    <x-page-toolbar title="Edit partner" meta="{{ $partner->name }}">
        <x-slot:actions>
            <a href="{{ route('research.partners.index') }}" class="tich-btn tich-btn-secondary">Back to list</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if ($errors->any())
        <div class="tich-alert tich-alert--error tich-mt-4">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <div class="uf-form">
        <form method="POST" action="{{ route('research.partners.update', $partner) }}" enctype="multipart/form-data" data-uf="ready">
            @csrf
            @method('PUT')

            @include('research.partners._form', ['partner' => $partner])

            <div class="uf-form-section">
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <a href="{{ route('research.partners.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                        <button type="submit" class="uf-btn uf-btn-primary">Save partner</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

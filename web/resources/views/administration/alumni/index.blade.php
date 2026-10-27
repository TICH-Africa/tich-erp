@extends('layouts.administration')

@section('title', 'Alumni register')

@section('administration-content')
    <x-page-toolbar title="Alumni register" meta="{{ $total }} graduate(s) from past years - each stays on the student record the school already has">
        <x-slot:actions>
            <a href="{{ route('administration.alumni.add') }}" class="tich-btn tich-btn-primary">Add a graduate</a>
            <a href="{{ route('administration.alumni.create') }}" class="tich-btn tich-btn-secondary">Register a student already on file</a>
            <a href="{{ route('administration.alumni.upload') }}" class="tich-btn tich-btn-secondary">Upload a past list</a>
        </x-slot:actions>
        <x-slot:filters>
            @include('partials.alumni-filters', ['action' => route('administration.alumni.index')])
        </x-slot:filters>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif

    @if (session('import_problems') !== null && session('import_problems') !== [])
        <div class="tich-alert tich-alert--error tich-mt-4">
            <p><strong>Lines that were not registered</strong></p>
            <ul class="tich-mt-2" style="margin:0; padding-left:1.1rem;">
                @foreach (session('import_problems') as $problem)
                    <li>{{ $problem }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @include('partials.alumni-table', [
        'students' => $students,
        'editable' => true,
        'showRoute' => 'administration.applications.existing-student.show',
    ])
@endsection

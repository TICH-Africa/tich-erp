@extends('layouts.academics')

@section('title', 'Alumnus profile')

@section('academics-content')
    <x-page-toolbar :title="$student->displayName()" meta="Alumni file · registered by Admissions · read only here">
        <x-slot:actions>
            <a href="{{ route('academics.alumni.index') }}" class="tich-btn tich-btn-ghost">Back to Alumni</a>
        </x-slot:actions>
    </x-page-toolbar>

    @include('partials.alumni-file', ['student' => $student])

    <p class="tich-caption tich-mt-4">
        Marks, transcripts and fee history stay on the student record itself, which Admissions and
        Finance keep. This page is the alumni file only.
    </p>
@endsection

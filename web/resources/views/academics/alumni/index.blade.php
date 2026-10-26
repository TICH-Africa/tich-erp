@extends('layouts.academics')

@section('title', 'Alumni')

@section('academics-content')
    <x-page-toolbar title="Alumni" meta="{{ $total }} graduate(s) registered by Admissions - read only here">
        <x-slot:filters>
            @include('partials.alumni-filters', ['action' => route('academics.alumni.index')])
        </x-slot:filters>
    </x-page-toolbar>

    @include('partials.alumni-table', [
        'students' => $students,
        'editable' => false,
        'showRoute' => 'academics.students.show',
    ])
@endsection

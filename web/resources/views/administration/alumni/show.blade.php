@extends('layouts.administration')

@section('title', 'Alumnus profile')

@section('administration-content')
    <x-page-toolbar :title="$student->displayName()" meta="Alumni file · {{ $student->registration_number ?: 'no admission number' }}">
        <x-slot:actions>
            <a href="{{ route('administration.alumni.edit', $student) }}" class="tich-btn tich-btn-secondary">Edit graduation details</a>
            <a href="{{ route('administration.alumni.index') }}" class="tich-btn tich-btn-ghost">Back to the register</a>
        </x-slot:actions>
    </x-page-toolbar>

    @include('partials.alumni-file', ['student' => $student])
@endsection

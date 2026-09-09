@extends('layouts.qa')

@section('title', 'Build assessment sheet')

@section('qa-content')
    <x-page-toolbar
        title="Build assessment sheet"
        meta="Select department(s), define evaluation criteria, then dispatch"
    >
        <x-slot:actions>
            <a href="{{ route('qa.assessments.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    @include('qa.assessments.partials.form')
@endsection

@section('scripts')
    @parent
    @include('qa.assessments.partials.form-script')
@endsection

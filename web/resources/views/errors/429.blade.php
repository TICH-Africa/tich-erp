@extends('errors.layout')

@section('title', 'Too many requests')

@section('error-body')
    @include('errors.partials.page', [
        'code' => '429',
        'title' => 'Too many requests',
        'message' => 'You have made too many requests in a short time. Please wait a moment and try again.',
        'hint' => 'If you were submitting a form repeatedly, wait a few seconds before retrying.',
    ])
@endsection

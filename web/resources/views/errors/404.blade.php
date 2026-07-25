@extends('errors.layout')

@section('title', 'Page not found')

@section('error-body')
    @include('errors.partials.page', [
        'code' => '404',
        'title' => 'Page not found',
        'message' => 'The page you requested does not exist or may have been moved.',
        'hint' => 'Check the address for typos, or use the links below to get back on track.',
    ])
@endsection

@extends('errors.layout')

@section('title', 'Service unavailable')

@section('error-body')
    @include('errors.partials.page', [
        'code' => '503',
        'title' => 'Service unavailable',
        'message' => 'The platform is temporarily unavailable while we perform maintenance or recover from an outage.',
        'hint' => 'Please check back shortly. Your data is safe.',
    ])
@endsection

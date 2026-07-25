@extends('errors.layout')

@section('title', 'Something went wrong')

@section('error-body')
    @include('errors.partials.page', [
        'code' => '500',
        'title' => 'Something went wrong',
        'message' => 'We hit an unexpected problem while processing your request.',
        'hint' => 'Our team has been notified. Please try again in a few minutes, or return to your dashboard.',
    ])
@endsection

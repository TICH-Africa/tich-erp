@extends('errors.layout')

@section('title', 'Method not allowed')

@section('error-body')
    @include('errors.partials.page', [
        'code' => '405',
        'title' => 'Method not allowed',
        'message' => 'This page does not support the action you attempted.',
        'hint' => 'Use the navigation links below or go back to the previous screen.',
    ])
@endsection

@extends('errors.layout')

@section('title', 'Access denied')

@section('error-body')
    @include('errors.partials.page', [
        'code' => '403',
        'title' => 'Access denied',
        'message' => 'You do not have permission to view this page.',
        'hint' => 'If you believe this is a mistake, contact your administrator or try signing in with a different account.',
    ])
@endsection

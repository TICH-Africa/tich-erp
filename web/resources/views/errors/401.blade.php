@extends('errors.layout')

@section('title', 'Sign in required')

@section('error-body')
    @include('errors.partials.page', [
        'code' => '401',
        'title' => 'Sign in required',
        'message' => 'You need to be signed in to access this page.',
        'hint' => 'Your session may have ended. Sign in again to continue where you left off.',
        'actions' => [
            ['label' => 'Sign in', 'url' => route('login'), 'primary' => true],
            ['label' => 'Go to homepage', 'url' => route('home'), 'primary' => false],
        ],
    ])
@endsection

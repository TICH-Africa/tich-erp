@extends('errors.layout')

@section('title', 'Session expired')

@section('error-body')
    @php
        $errorNav = app(\App\Services\ErrorNavigationService::class);
    @endphp
    @include('errors.partials.page', [
        'code' => '419',
        'title' => 'Session expired',
        'message' => 'Your session timed out for security. Refresh the page or sign in again, then retry your last action.',
        'hint' => 'This often happens after being idle for a while or when a form was open too long.',
        'actions' => auth()->check()
            ? [
                ['label' => $errorNav->homeLabel(), 'url' => $errorNav->homeUrl(), 'primary' => true],
                ['label' => 'Sign in again', 'url' => route('login'), 'primary' => false],
            ]
            : [
                ['label' => 'Sign in', 'url' => route('login'), 'primary' => true],
                ['label' => 'Go to homepage', 'url' => route('home'), 'primary' => false],
            ],
    ])
@endsection

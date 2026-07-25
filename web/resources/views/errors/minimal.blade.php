@extends('errors.layout')

@section('title', 'Something went wrong')

@section('error-body')
    @php
        $statusCode = $exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
            ? $exception->getStatusCode()
            : null;
    @endphp
    @include('errors.partials.page', [
        'code' => $statusCode ?? 'Error',
        'title' => 'Something went wrong',
        'message' => $statusCode
            ? ($exception->getMessage() ?: 'We could not complete your request.')
            : 'We could not complete your request.',
        'hint' => 'Use the links below to return to a safe page.',
    ])
@endsection

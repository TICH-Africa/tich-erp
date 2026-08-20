@extends('errors.layout')

@section('title', 'Access denied')

@section('error-body')
    @include('errors.partials.page', [
        'code' => '403',
        'title' => 'Access denied',
        'message' => trim($exception->getMessage() ?? '') !== ''
            ? $exception->getMessage()
            : 'You do not have permission to view this page.',
        'hint' => 'If you just created your account, ask ICT or HR to assign your department role. After signing in you can also open /start for next steps.',
    ])
@endsection

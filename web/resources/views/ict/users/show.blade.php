@extends('layouts.ict')

@section('title', $user->displayName() . ' - User profile')

@section('ict-content')
    @include('partials.access-management.users-show')
@endsection

@extends('layouts.app')

@section('title', 'Student Information System')

@section('content')
<div class="tich-admin">
    @include('sis.partials.sidebar')

    <div class="tich-admin__main">
        @include('partials.alerts')

        @yield('sis-content')
    </div>
</div>
@endsection

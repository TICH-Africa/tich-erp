@extends('layouts.app')

@section('title', 'Academics')

@section('content')
<div class="tich-admin">
    @include('academics.partials.sidebar')

    <div class="tich-admin__main">
        @include('partials.alerts')

        @yield('academics-content')
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Administration')

@section('content')
<div class="tich-admin">
    @include('administration.partials.sidebar')

    <div class="tich-admin__main">
        @include('partials.alerts')
        @yield('administration-content')
    </div>
</div>
@endsection

@section('scripts')
    @parent
    @include('partials.navigation.sidebar-realtime-config')
    <x-asset.script path="js/tich-sidebar.js" />
@endsection

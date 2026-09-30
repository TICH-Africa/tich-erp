@extends('layouts.app')

@section('title', 'Chief Institution Administrator')

@section('content')
<div class="tich-admin">
    @include('institution-admin.partials.sidebar')

    <div class="tich-admin__main">
        @include('partials.alerts')
        @include('institution-admin.partials.global-search')

        @yield('institution-admin-content')
    </div>
</div>
@endsection

@section('scripts')
    @parent
    @include('partials.navigation.sidebar-realtime-config')
    <x-asset.script path="js/tich-sidebar.js" />
    <x-asset.script path="js/tich-ceo-search.js" />
@endsection

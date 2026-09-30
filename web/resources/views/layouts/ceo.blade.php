@extends('layouts.app')

@section('title', 'Chief Executive Officer')

@section('content')
<div class="tich-admin">
    @include('ceo.partials.sidebar')

    <div class="tich-admin__main">
        @include('partials.alerts')
        @include('ceo.partials.global-search')

        @yield('ceo-content')
    </div>
</div>
@endsection

@section('scripts')
    @parent
    @include('partials.navigation.sidebar-realtime-config')
    <x-asset.script path="js/tich-sidebar.js" />
    <x-asset.script path="js/tich-ceo-search.js" />
@endsection

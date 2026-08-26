@extends('layouts.app')

@section('title', 'Finance')

@section('content')
<div class="tich-admin">
    @include('finance.partials.sidebar')

    <div class="tich-admin__main">
        @include('partials.alerts')

        @yield('finance-content')
    </div>
</div>
@endsection

@section('scripts')
    @parent
    @include('partials.navigation.sidebar-realtime-config')
    <x-asset.script path="js/tich-sidebar.js" />
@endsection

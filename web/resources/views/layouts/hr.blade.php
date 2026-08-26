@extends('layouts.app')

@section('title', 'HR')

@section('content')
<div class="tich-admin">
    @include('hr.partials.sidebar')

    <div class="tich-admin__main">
        @include('partials.alerts')

        @yield('hr-content')
    </div>
</div>
@endsection

@section('scripts')
    @parent
    @include('partials.navigation.sidebar-realtime-config')
    <x-asset.script path="js/tich-sidebar.js" />
@endsection

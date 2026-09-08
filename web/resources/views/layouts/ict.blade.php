@extends('layouts.app')

@section('title', 'ICT')

@section('content')
<div class="tich-admin">
    @include('ict.partials.sidebar')

    <div class="tich-admin__main">
        @include('partials.alerts')
        @yield('ict-content')
    </div>
</div>
@endsection

@section('scripts')
    @parent
    @include('partials.navigation.sidebar-realtime-config')
    <x-asset.script path="js/tich-sidebar.js" />
@endsection

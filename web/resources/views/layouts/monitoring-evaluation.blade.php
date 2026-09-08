@extends('layouts.app')

@section('title', 'Monitoring & evaluation')

@section('content')
<div class="tich-admin">
    @include('monitoring-evaluation.partials.sidebar')

    <div class="tich-admin__main">
        @include('partials.alerts')
        @yield('monitoring-evaluation-content')
    </div>
</div>
@endsection

@section('scripts')
    @parent
    @include('partials.navigation.sidebar-realtime-config')
    <x-asset.script path="js/tich-sidebar.js" />
@endsection

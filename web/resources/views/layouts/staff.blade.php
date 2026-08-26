@extends('layouts.app')

@section('title', $portalTitle ?? 'Staff portal')

@section('content')
<div class="tich-admin">
    @include('staff.partials.sidebar')

    <div class="tich-admin__main">
        @include('partials.alerts')

        @include('staff.partials.teaching-context')

        @yield('staff-content')
    </div>
</div>
@endsection

@section('scripts')
    @parent
    @include('partials.navigation.sidebar-realtime-config')
    <x-asset.script path="js/tich-sidebar.js" />
@endsection

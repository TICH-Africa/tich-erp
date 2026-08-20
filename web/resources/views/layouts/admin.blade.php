@extends('layouts.app')

@section('title', 'Administration')

@section('content')
<div class="tich-admin">
    @include('admin.partials.sidebar')

    <div class="tich-admin__main">
        @include('partials.alerts')

        @yield('admin-content')
    </div>
</div>
@endsection

@section('scripts')
    @parent
    @include('partials.navigation.sidebar-realtime-config')
    <script src="{{ asset('js/tich-sidebar.js') }}" defer></script>
@endsection

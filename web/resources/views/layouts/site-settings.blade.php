@extends('layouts.app')

@section('title', 'Site settings')

@section('content')
<div class="tich-admin">
    @include('site-settings.partials.sidebar', ['panel' => $panel ?? 'general'])

    <div class="tich-admin__main">
        @include('partials.alerts')

        @yield('site-settings-content')
    </div>
</div>
@endsection

@section('scripts')
    @parent
    <script src="{{ asset('js/tich-sidebar.js') }}" defer></script>
@endsection

@extends('layouts.app')

@section('title', 'Research')

@section('content')
<div class="tich-admin">
    @include('research.partials.sidebar')

    <div class="tich-admin__main">
        @include('partials.alerts')
        @yield('research-content')
    </div>
</div>
@endsection

@section('scripts')
    @parent
    <x-asset.script path="js/tich-sidebar.js" />
@endsection

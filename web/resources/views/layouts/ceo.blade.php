@extends('layouts.app')

@section('title', 'Chief Executive Officer')

@section('content')
<div class="tich-admin">
    @include('ceo.partials.sidebar')

    <div class="tich-admin__main">
        @include('partials.alerts')

        @yield('ceo-content')
    </div>
</div>
@endsection

@section('scripts')
    @parent
    <x-asset.script path="js/tich-sidebar.js" />
@endsection

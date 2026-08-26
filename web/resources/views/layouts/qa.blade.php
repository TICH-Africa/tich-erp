@extends('layouts.app')

@section('title', 'Quality Assurance')

@section('content')
<div class="tich-admin">
    @include('qa.partials.sidebar')

    <div class="tich-admin__main">
        @include('partials.alerts')
        @yield('qa-content')
    </div>
</div>
@endsection

@section('scripts')
    @parent
    <x-asset.script path="js/tich-sidebar.js" />
@endsection

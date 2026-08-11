@extends('layouts.app')

@section('title', 'Procurement')

@section('content')
<div class="tich-admin">
    @include('procurement.partials.sidebar')

    <div class="tich-admin__main">
        @include('partials.alerts')
        @yield('procurement-content')
    </div>
</div>
@endsection

@section('scripts')
    @parent
    <script src="{{ asset('js/tich-sidebar.js') }}" defer></script>
@endsection

@extends('layouts.app')

@section('title', 'Academics')

@section('content')
<div class="tich-admin">
    @include('academics.partials.sidebar', [
        'department' => $department,
        'learningDepartment' => $learningDepartment ?? null,
    ])

    <div class="tich-admin__main">
        @include('partials.alerts')

        @yield('academics-content')
    </div>
</div>
@endsection

@section('scripts')
    @parent
    @include('partials.navigation.sidebar-realtime-config')
    <x-asset.script path="js/tich-sidebar.js" />
@endsection

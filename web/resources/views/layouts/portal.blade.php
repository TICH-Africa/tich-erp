@extends('layouts.app')

@section('title', $portalTitle ?? 'Student portal')

@section('content')
<div class="tich-admin">
    @include('portal.partials.sidebar', [
        'student' => $student,
        'biodata' => $biodata,
        'sidebarNavigation' => $sidebarNavigation,
        'section' => $section ?? null,
        'tab' => $tab ?? null,
    ])

    <div class="tich-admin__main">
        @include('partials.alerts')

        @yield('portal-content')
    </div>
</div>
@endsection

@section('scripts')
    @parent
    @include('partials.navigation.sidebar-realtime-config')
    <script src="{{ asset('js/tich-sidebar.js') }}" defer></script>
@endsection

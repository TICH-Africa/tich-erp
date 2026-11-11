@extends('layouts.app')

@section('title', $portalTitle ?? 'Student portal')

@section('content')
@php $portalHasSidebar = isset($student) && isset($biodata) && isset($sidebarNavigation); @endphp
<div class="tich-admin tich-admin--student-portal{{ $portalHasSidebar ? '' : ' tich-admin--no-sidebar' }}">
    @if ($portalHasSidebar)
        @include('portal.partials.sidebar', [
            'student' => $student,
            'biodata' => $biodata,
            'sidebarNavigation' => $sidebarNavigation,
            'section' => $section ?? null,
            'tab' => $tab ?? null,
        ])
    @endif

    <div class="tich-admin__main">
        @include('partials.alerts')

        @yield('portal-content')
    </div>
</div>
@endsection

@section('scripts')
    @parent
    @include('partials.navigation.sidebar-realtime-config')
    <x-asset.script path="js/tich-sidebar.js" />
@endsection

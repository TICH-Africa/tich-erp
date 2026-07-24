@extends('layouts.app')

@section('title', $portalTitle ?? 'Student portal')

@section('content')
<div class="tich-admin">
    @include('portal.partials.sidebar', [
        'student' => $student,
        'biodata' => $biodata,
        'sidebarNavigation' => $sidebarNavigation,
    ])

    <div class="tich-admin__main">
        @include('partials.alerts')

        @yield('portal-content')
    </div>
</div>
@endsection

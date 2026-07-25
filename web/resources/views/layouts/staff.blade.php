@extends('layouts.app')

@section('title', $portalTitle ?? 'Staff portal')

@section('content')
<div class="tich-admin">
    @include('staff.partials.sidebar')

    <div class="tich-admin__main">
        @include('partials.alerts')

        @yield('staff-content')
    </div>
</div>
@endsection

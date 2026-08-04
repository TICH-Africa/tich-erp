@extends('layouts.app')

@section('title', 'HR')

@section('content')
<div class="tich-admin">
    @include('hr.partials.sidebar')

    <div class="tich-admin__main">
        <div style="display: flex; justify-content: flex-end; padding: 16px 24px 0 0;">
            @include('partials.notification-bell')
        </div>

        @include('partials.alerts')

        @yield('hr-content')
    </div>
</div>
@endsection

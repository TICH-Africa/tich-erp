@extends('layouts.app')

@section('title', 'Approval dashboard')

@section('content')
<div class="tich-admin">
    @include('admissions.partials.sidebar')

    <div class="tich-admin__main">
        @include('partials.alerts')

        @yield('approval-content')
    </div>
</div>
@endsection

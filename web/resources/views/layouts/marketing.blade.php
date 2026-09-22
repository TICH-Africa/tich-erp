@extends('layouts.app')

@section('title', 'Marketing')

@section('content')
<div class="tich-admin tich-dept-dashboard">
    @include('marketing.partials.sidebar')

    <div class="tich-admin__main">
        @include('partials.alerts')

        @yield('department-content')
    </div>
</div>
@endsection

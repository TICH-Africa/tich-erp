@extends('layouts.app')

@section('title', $department->dept_name)

@section('content')
<div class="tich-admin tich-dept-dashboard">
    @include('departments.partials.sidebar')

    <div class="tich-admin__main">
        @include('partials.alerts')

        @yield('department-content')
    </div>
</div>
@endsection

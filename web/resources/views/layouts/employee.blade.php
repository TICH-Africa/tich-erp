@extends('layouts.app')

@section('title', $portalTitle ?? 'My Employee Portal')

@section('content')
<div class="tich-admin">
    @include('employee.partials.sidebar')

    <div class="tich-admin__main">
        @include('partials.alerts')

        @if (auth()->user()->isTeachingStaff())
            <article class="tich-card tich-card--highlight tich-mb-6">
                <p class="tich-caption">Teaching</p>
                <h3 class="tich-h3 tich-mt-2">Staff portal</h3>
                <p class="tich-text tich-mt-2">Manage lesson plans, attendance, grading, and your teaching timetable.</p>
                <a href="{{ route('staff.dashboard') }}" class="tich-btn tich-btn-secondary tich-mt-4">Open staff portal</a>
            </article>
        @endif

        @yield('employee-content')
    </div>
</div>
@endsection

@extends('layouts.ceo')

@section('title', 'Academics overview')

@section('ceo-content')
    <x-page-toolbar title="Academics overview" meta="Institution-wide academics snapshot for executive oversight" />

    <div class="tich-grid tich-grid--4 tich-mt-8">
        <article class="tich-card">
            <p class="tich-caption">Programmes</p>
            <p class="tich-h2 tich-mt-2">{{ $stats['programs'] }}</p>
        </article>
        <article class="tich-card">
            <p class="tich-caption">Units</p>
            <p class="tich-h2 tich-mt-2">{{ $stats['units'] }}</p>
        </article>
        <article class="tich-card">
            <p class="tich-caption">Learning departments</p>
            <p class="tich-h2 tich-mt-2">{{ $stats['learning_departments'] }}</p>
        </article>
        <article class="tich-card tich-card--highlight">
            <p class="tich-caption">Awaiting CEO</p>
            <p class="tich-h2 tich-mt-2">{{ $stats['pending_ceo_versions'] }}</p>
            <p class="tich-caption tich-mt-2">curriculum version(s)</p>
        </article>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-8">
        <article class="tich-card">
            <h2 class="tich-h3">Curriculum sign-off</h2>
            <p class="tich-text tich-mt-2">
                {{ $stats['pending_ceo_versions'] }} intake version(s) and {{ $stats['pending_ceo_programs'] }} programme(s) are pending executive publication.
            </p>
            <a href="{{ route('ceo.curriculum.index') }}" class="tich-btn tich-btn-primary tich-mt-4">Open curriculum sign-off</a>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Learning departments</h2>
            <ul class="tich-mt-4" style="margin:0; padding-left:1.25rem;">
                @forelse ($learningDepartments as $department)
                    <li class="tich-text">{{ $department->dept_name }} <span class="tich-caption">({{ $department->dept_code }})</span></li>
                @empty
                    <li class="tich-text">No learning departments configured yet.</li>
                @endforelse
            </ul>
            @if ($hub)
                <p class="tich-caption tich-mt-4">Hub: {{ $hub->dept_name }}</p>
            @endif
        </article>
    </div>
@endsection

@extends('layouts.department')

@section('title', $department->dept_name)

@section('department-content')
    <x-page-toolbar title="{{ $department->dept_name }}" meta="{{ $department->dept_code }} · {{ $department->dept_category }}" />

    @if ($modules !== [])
        <div class="tich-grid tich-grid--2 tich-mt-6">
            @foreach ($modules as $module)
                <article class="tich-card">
                    <h3 class="tich-h3">{{ $module['label'] }}</h3>
                    <p class="tich-text tich-mt-2">{{ $module['description'] }}</p>
                    <a href="{{ route($module['route'], $module['params'] ?? []) }}" class="tich-btn tich-btn-primary tich-mt-4">Open</a>
                </article>
            @endforeach
        </div>
    @else
        <div class="tich-card tich-mt-6">
            <p class="tich-text">No tools available for this department.</p>
        </div>
    @endif
@endsection
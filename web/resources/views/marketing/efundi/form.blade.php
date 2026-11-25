@extends('layouts.marketing')

@section('title', $opportunity->exists ? 'Edit e-Fundi opportunity' : 'New e-Fundi opportunity')

@section('department-content')
    <x-page-toolbar :title="$opportunity->exists ? 'Edit opportunity' : 'New opportunity'" meta="External listings need a valid application URL and/or email below.">
        <a href="{{ $publicUrl }}" class="tich-btn tich-btn-ghost" target="_blank" rel="noopener">View public page</a>
    </x-page-toolbar>

    @include('efundi.partials.form', [
        'routePrefix' => $routePrefix,
        'publicUrl' => $publicUrl,
        'types' => $types,
        'scopes' => $scopes,
        'linkableVacancies' => $linkableVacancies,
        'opportunity' => $opportunity,
    ])
@endsection
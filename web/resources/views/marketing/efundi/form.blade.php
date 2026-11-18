@extends('layouts.marketing')

@section('title', $opportunity->exists ? 'Edit e-Fundi opportunity' : 'New e-Fundi opportunity')

@section('department-content')
    <x-page-toolbar :title="$opportunity->exists ? 'Edit opportunity' : 'New opportunity'" :meta="$publicUrl" />

    @include('efundi.partials.form', [
        'routePrefix' => $routePrefix,
        'publicUrl' => $publicUrl,
        'types' => $types,
        'scopes' => $scopes,
        'linkableVacancies' => $linkableVacancies,
        'opportunity' => $opportunity,
    ])
@endsection
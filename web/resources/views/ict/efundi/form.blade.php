@extends('layouts.ict')

@section('title', $opportunity->exists ? 'Edit e-Fundi opportunity' : 'New e-Fundi opportunity')

@section('ict-content')
    <x-page-toolbar :title="$opportunity->exists ? 'Edit opportunity' : 'New opportunity'" :meta="$publicUrl" />

    @include('efundi.partials.form', [
        'routePrefix' => $routePrefix,
        'publicUrl' => $publicUrl,
        'types' => $types,
        'opportunity' => $opportunity,
    ])
@endsection
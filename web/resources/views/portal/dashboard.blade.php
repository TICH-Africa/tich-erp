@extends('layouts.portal')

@section('portal-content')
    @include('portal.partials.section-' . $section)
@endsection

@extends('layouts.staff')

@section('staff-content')
    @include('staff.partials.section-' . $section)
@endsection

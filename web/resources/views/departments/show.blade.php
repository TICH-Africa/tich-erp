@extends('layouts.department')

@section('department-content')
    @if ($dashboardViewType === 'hub')
        @include($section === 'departments' ? 'departments.partials.overview-departments' : 'departments.partials.overview-hub')
    @else
        @include(match ($dashboardViewType) {
            'academic' => 'departments.partials.overview-academic',
            'operational' => 'departments.partials.overview-operational',
            default => 'departments.partials.overview-empty',
        })
    @endif
@endsection

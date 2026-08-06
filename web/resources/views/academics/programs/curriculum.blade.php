@extends('layouts.academics')

@section('academics-content')
    @php
        $hub = \App\Support\AcademicsRouteParams::for([
            'learning_department' => ($learningDepartment ?? null)?->id ?? request()->integer('learning_department') ?: null,
        ]);
        if (! empty($learningDepartment)) {
            $hub['learning_department'] = $learningDepartment->id;
        }
        $mappingIndex = 0;
        $assignedUnitIds = $mappings->pluck('unit_id')->all();
        $curriculumParams = array_filter(array_merge($hub, [
            'program' => $program->id,
            'intake' => $selectedIntake?->id,
            'section' => $section,
        ]));
    @endphp

    @error('intake')
        <p class="tich-text tich-mb-4" style="color: var(--tich-danger, #b91c1c);">{{ $message }}</p>
    @enderror

    @include('academics.programs.partials.working-intake-bar')

    @if ($intakeSelectionRequired ?? false)
        @include('academics.programs.partials.intake-selection-required')
    @else
        @include('academics.programs.partials.curriculum-section-' . $section)
    @endif
@endsection

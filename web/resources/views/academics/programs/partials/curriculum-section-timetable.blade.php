@if (! $selectedIntake)
    <article class="tich-card">
        <p class="tich-text">Select or <a href="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'section' => 'intakes'])) }}" class="tich-link">create an intake</a> before building a timetable.</p>
    </article>
@elseif (request()->boolean('timetable_edit'))
    @if (in_array(request()->string('timetable_workspace')->toString(), ['builder', 'upload'], true))
        @include('academics.programs.partials.curriculum-section-timetable-edit')
    @else
        @include('academics.programs.partials.curriculum-section-timetable-choose')
    @endif
@else
    @include('academics.programs.partials.curriculum-section-timetable-view')
@endif

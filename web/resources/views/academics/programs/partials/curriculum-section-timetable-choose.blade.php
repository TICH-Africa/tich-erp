@php
    $kindLabel = $timetableKinds[$timetableKind] ?? 'Timetable';
    $kindTimetable = ($timetableDraftsByKind ?? collect())[$timetableKind] ?? null;
    $workspaceParams = fn (string $workspace) => array_merge($curriculumParams, array_filter([
        'section' => 'timetable',
        'teaching_period' => $timetableTeachingPeriod,
        'timetable_kind' => $timetableKind,
        'timetable_edit' => 1,
        'timetable_workspace' => $workspace,
    ]));
    $viewUrl = route('departments.academics.programs.curriculum', array_merge($curriculumParams, [
        'section' => 'timetable',
        'teaching_period' => $timetableTeachingPeriod,
    ])).'#timetable-display';
@endphp

<x-page-toolbar
    title="{{ $kindLabel }} — {{ $program->periodLabel($timetableTeachingPeriod) }}"
    meta="Choose how to create or edit this timetable"
    class="tich-mb-6"
/>

<p class="tich-mb-6">
    <a href="{{ $viewUrl }}" class="tich-link">← Back to timetable documents</a>
</p>

<article class="tich-card tich-mb-8" id="timetable-edit">
    <h2 class="tich-h3">How do you want to work on this timetable?</h2>
    <p class="tich-text tich-mt-2">
        @if ($kindTimetable)
            You already have a {{ strtolower($kindLabel) }} for this semester. Pick the workspace that matches how you maintain it.
        @else
            Start a new {{ strtolower($kindLabel) }} for {{ $selectedIntake->intakeLabel() }} · {{ $program->periodLabel($timetableTeachingPeriod) }}.
        @endif
    </p>

    <div class="tich-mt-6" style="display:grid; gap:1.25rem; grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr));">
        <a
            href="{{ route('departments.academics.programs.curriculum', $workspaceParams('builder')) }}#timetable-edit"
            class="tich-inset-panel"
            style="display:block; text-decoration:none; color:inherit; margin:0;"
        >
            <h3 class="tich-h3" style="font-size:1.125rem;">Grid builder</h3>
            <p class="tich-caption tich-mt-2" style="margin-bottom:0;">
                Build or edit classes in the ERP grid — bell schedule, drag-and-drop, and session editor.
                @if ($kindTimetable && $kindTimetable->hasUploadedDocument())
                    <span class="tich-text"> Switching here can replace an uploaded file.</span>
                @endif
            </p>
            <span class="tich-btn tich-btn-primary tich-mt-4">Open grid builder</span>
        </a>

        <a
            href="{{ route('departments.academics.programs.curriculum', $workspaceParams('upload')) }}#timetable-edit"
            class="tich-inset-panel"
            style="display:block; text-decoration:none; color:inherit; margin:0;"
        >
            <h3 class="tich-h3" style="font-size:1.125rem;">Upload document</h3>
            <p class="tich-caption tich-mt-2" style="margin-bottom:0;">
                Upload PDF, image, Word, or Excel — shown as-is to students and staff.
                @if ($kindTimetable && ! $kindTimetable->hasUploadedDocument() && $kindTimetable->sessions->count() > 0)
                    <span class="tich-text"> Uploading replaces grid classes for this timetable.</span>
                @endif
            </p>
            <span class="tich-btn tich-btn-secondary tich-mt-4">Open upload</span>
        </a>
    </div>
</article>

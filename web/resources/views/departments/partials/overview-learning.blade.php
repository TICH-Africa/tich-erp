<x-page-toolbar
    title="{{ $department->dept_name }}"
    meta="{{ $overviewStats['category'] }} · {{ $department->dept_code }} · Programmes, units, and applications"
/>

<!-- <div class="tich-grid tich-grid--4 tich-dept-stats">
    <article class="tich-card tich-stat">
        <p class="tich-caption">Programmes</p>
        <p class="tich-stat__value">{{ $overviewStats['program_count'] ?? 0 }}</p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Catalog units</p>
        <p class="tich-stat__value">{{ $overviewStats['unit_count'] ?? 0 }}</p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Pending applications</p>
        <p class="tich-stat__value">{{ $overviewStats['pending_applications'] ?? 0 }}</p>
    </article>
    <article class="tich-card tich-stat">
        <p class="tich-caption">Curriculum profile</p>
        <p class="tich-stat__value" style="font-size: 1rem;">{{ ucfirst($overviewStats['curriculum_profile'] ?? 'standard') }}</p>
    </article>
</div> -->

@php
    $educationModules = collect($modules)->where('group', 'education');
    $admissionsModules = collect($modules)->where('group', 'admissions');
    $programsModule = $educationModules->firstWhere('route', 'departments.academics.programs.index');
    $curriculumScope = $programsModule['params'] ?? null;
@endphp

<section class="tich-dept-panel tich-mt-8">
    <div class="tich-dept-panel__head">
        <h2 class="tich-h2 tich-dept-panel__title">Programmes offered</h2>
        <p class="tich-text">Academic programmes under {{ $department->dept_name }}.</p>
    </div>

    @if ($programs->isNotEmpty())
        <div class="tich-card tich-table-panel">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Programme</th>
                        <th>Duration</th>
                        <th>Terms / year</th>
                        <th>Format</th>
                        @if ($curriculumScope)
                            <th></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($programs as $program)
                        <tr>
                            <td>{{ $program->program_code }}</td>
                            <td>{{ $program->program_name }}</td>
                            <td>{{ $program->duration_months ? $program->duration_months.' months' : '-' }}</td>
                            <td>{{ $program->semester_count ?: $program->termsPerYear() }}</td>
                            <td>{{ $curriculumFormats[$program->curriculum_format ?? 'trimester'] ?? ucfirst($program->curriculum_format ?? 'trimester') }}</td>
                            @if ($curriculumScope)
                                <td>
                                    <a href="{{ route('departments.academics.programs.curriculum', array_merge($curriculumScope, ['program' => $program->id, 'section' => 'intakes'])) }}" class="tich-link">Open builder</a>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

       
   
    @endif
</section>



@if ($admissionsModules->isNotEmpty())
    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">Admissions</h2>
            <p class="tich-text">Review student applications routed to this department.</p>
        </div>

        <div class="tich-grid tich-grid--2 tich-dept-cards">
            @foreach ($admissionsModules as $module)
                <article class="tich-card tich-dept-card">
                    <h3 class="tich-h3">{{ $module['label'] }}</h3>
                    <p class="tich-text tich-mt-2">{{ $module['description'] }}</p>
                    <a href="{{ route($module['route'], $module['params'] ?? []) }}" class="tich-btn tich-btn-secondary tich-mt-4">Open</a>
                </article>
            @endforeach
        </div>
    </section>
@endif

@if ($modules === [])
    <article class="tich-card tich-dept-empty tich-mt-8">
        <h2 class="tich-h3">No education tools available</h2>
        <p class="tich-text tich-mt-2">You do not have permission to manage curriculum or applications for this department.</p>
    </article>
@endif

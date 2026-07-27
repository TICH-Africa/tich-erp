@extends('layouts.academics')

@section('academics-content')
    @php
        $hub = ['department' => $department->id];
    @endphp

    @include('academics.partials.learning-department-context')

    <div class="tich-section__intro" style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:1rem; text-align:left;">
        <div>
            <h1 class="tich-h1" style="font-size: 2rem;">Nursing Block Progression</h1>
            <p class="tich-text">Track clinical logs and skills assessments for {{ $program->program_name }}.</p>
        </div>
    </div>

    @if ($blocks->isEmpty())
        <p class="tich-text tich-mt-8">No nursing blocks configured for this program.</p>
    @else
        @foreach ($blocks as $block)
            <article class="tich-card tich-mt-6">
                <h2 class="tich-h3">{{ $block->block_label }}</h2>
                <p class="tich-caption">Student progression status</p>

                @if ($block->students->isEmpty())
                    <p class="tich-text tich-mt-4">No nursing students enrolled in this block.</p>
                @else
                    <table class="tich-admin-table tich-mt-4">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Clinical Passed</th>
                                <th>Skills Passed</th>
                                <th>Units Completed</th>
                                <th>Progress Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($block->students as $student)
                                <tr>
                                    <td>{{ $student->registration_number }} - {{ $student->first_name }} {{ $student->surname }}</td>
                                    <td>{{ $student->clinical_passed }}</td>
                                    <td>{{ $student->skills_passed }}</td>
                                    <td>{{ $student->units_completed }}</td>
                                    <td>
                                        @if ($student->can_progress)
                                            <span class="tich-badge tich-badge--success">Ready</span>
                                        @else
                                            <span class="tich-badge tich-badge--warning">Incomplete</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </article>
        @endforeach
    @endif
@endsection
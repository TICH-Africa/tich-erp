@extends('layouts.academics')

@section('academics-content')
    <div class="tich-section__intro" style="text-align:left;">
        <h1 class="tich-h1" style="font-size: 2rem;">Programme curriculum</h1>
        <p class="tich-text">Select a programme to configure curriculum format, map units, and publish version snapshots.</p>
    </div>

    <div class="tich-card tich-mt-8" style="overflow-x:auto;">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Programme</th>
                    <th>Department</th>
                    <th>Format</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($programs as $program)
                    <tr>
                        <td>{{ $program->program_code }}</td>
                        <td>{{ $program->program_name }}</td>
                        <td>{{ $program->department?->dept_name ?? '—' }}</td>
                        <td>{{ $formats[$program->curriculum_format ?? 'trimester'] ?? ucfirst($program->curriculum_format ?? 'trimester') }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $program->status)) }}</td>
                        <td><a href="{{ route('academics.programs.curriculum', $program) }}" class="tich-link">Open builder</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="padding:2rem;text-align:center;" class="tich-text">No programmes in your scope.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

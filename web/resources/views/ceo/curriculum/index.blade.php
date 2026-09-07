@extends('layouts.ceo')

@section('title', 'Curriculum sign-off')

@section('ceo-content')
    <x-page-toolbar title="Curriculum sign-off" meta="Programme intakes awaiting CEO publication" />

    @error('curriculum')
        <div class="tich-alert tich-alert--error tich-mt-4">{{ $message }}</div>
    @enderror

    <div class="tich-card tich-table-panel tich-mt-8">
        <h2 class="tich-h3">Curriculum versions pending approval</h2>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Programme</th>
                        <th>Department</th>
                        <th>Intake</th>
                        <th>Registrar approved</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($versions as $version)
                        <tr>
                            <td>
                                <strong>{{ $version->program?->program_code }}</strong>
                                <p class="tich-caption">{{ $version->program?->program_name }}</p>
                            </td>
                            <td>{{ $version->program?->department?->dept_name ?? '-' }}</td>
                            <td>{{ $version->intakeLabel() }}</td>
                            <td>{{ $version->registrar_approved_at?->format('d M Y') ?? '-' }}</td>
                            <td>
                                <a href="{{ route('ceo.curriculum.show', $version) }}" class="tich-btn tich-btn-primary">Review</a>
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 5, 'title' => 'No curriculum versions awaiting CEO', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($versions instanceof \Illuminate\Contracts\Pagination\Paginator && $versions->hasPages())
            <div class="tich-mt-4">{{ $versions->links() }}</div>
        @endif
    </div>

    @if ($programs->isNotEmpty())
        <div class="tich-card tich-table-panel tich-mt-8">
            <h2 class="tich-h3">Programmes marked pending CEO</h2>
            <div class="tich-table-wrap tich-mt-4">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Programme</th>
                            <th>Department</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($programs as $program)
                            <tr>
                                <td>{{ $program->program_code }}</td>
                                <td>{{ $program->program_name }}</td>
                                <td>{{ $program->department?->dept_name ?? '-' }}</td>
                                <td><span class="tich-badge">pending CEO</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="tich-caption tich-mt-4">Programme status updates when the related curriculum version is published from the list above.</p>
        </div>
    @endif
@endsection

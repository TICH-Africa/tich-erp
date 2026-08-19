@extends('layouts.academics')

@section('academics-content')
    @php
        $hub = \App\Support\AcademicsRouteParams::for([
            'learning_department' => request()->integer('learning_department') ?: null,
        ]);
    @endphp

    <x-page-toolbar title="Academic clearance" meta="Confirm students are academically cleared for exams, progression, or graduation">
        <x-slot:actions>
            <a href="{{ route('departments.academics.dashboard', $hub) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-stat-row tich-mb-8">
        <div class="tich-stat">
            <p class="tich-stat__label">Academically cleared</p>
            <p class="tich-stat__value">{{ $clearedCount }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Pending clearance</p>
            <p class="tich-stat__value">{{ $pendingCount }}</p>
        </div>
    </div>

    <div class="tich-card tich-table-panel tich-mb-8">
        <form method="GET" action="{{ route('departments.academics.clearance.index', $hub) }}" class="tich-flex-wrap" style="gap: 0.75rem; align-items: end;">
            <div class="tich-form-group" style="margin: 0;">
                <label for="search" class="tich-label">Search</label>
                <input type="text" id="search" name="search" value="{{ $search }}" placeholder="Name or registration no." class="tich-input" style="min-width: 14rem;">
            </div>
            <div class="tich-form-group" style="margin: 0;">
                <label for="program_id" class="tich-label">Programme</label>
                <select id="program_id" name="program_id" class="tich-select" style="min-width: 14rem;">
                    <option value="0">All programmes</option>
                    @foreach ($programs as $program)
                        <option value="{{ $program->id }}" @selected($programId === $program->id)>
                            {{ $program->program_code }} - {{ $program->program_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="tich-btn tich-btn-secondary">Filter</button>
            @if ($search !== '' || $programId > 0)
                <a href="{{ route('departments.academics.clearance.index', $hub) }}" class="tich-btn tich-btn-ghost">Clear</a>
            @endif
        </form>
    </div>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Registration No.</th>
                        <th>Programme</th>
                        <th>Enrollment</th>
                        <th>Academic clearance</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        @php
                            $isCleared = ($student->academic_clearance_status ?? 'pending') === 'cleared';
                        @endphp
                        <tr>
                            <td><strong>{{ $student->fullName() }}</strong></td>
                            <td class="tich-caption">{{ $student->registration_number ?? '-' }}</td>
                            <td class="tich-caption">
                                {{ $student->program?->program_code ?? '-' }}
                                @if ($student->program?->program_name)
                                    · {{ $student->program->program_name }}
                                @endif
                            </td>
                            <td class="tich-caption">{{ ucfirst(str_replace('_', ' ', $student->enrollment_status ?? 'unknown')) }}</td>
                            <td>
                                @if ($isCleared)
                                    <span class="tich-badge tich-badge--success">CLEARED</span>
                                @else
                                    <span class="tich-badge tich-badge--warning">PENDING</span>
                                @endif
                            </td>
                            <td>
                                @if (! $isCleared)
                                    <form method="POST" action="{{ route('departments.academics.clearance.approve', array_merge($hub, ['student' => $student->id])) }}" class="tich-inline" onsubmit="return confirm('Clear this student academically?')">
                                        @csrf
                                        <button type="submit" class="tich-btn tich-btn-success">Clear</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('departments.academics.clearance.reject', array_merge($hub, ['student' => $student->id])) }}" class="tich-inline" onsubmit="return confirm('Revoke academic clearance for this student?')">
                                        @csrf
                                        <button type="submit" class="tich-btn tich-btn-danger">Revoke</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 6, 'title' => 'No students found', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($students instanceof \Illuminate\Contracts\Pagination\Paginator && $students->hasPages())
            <div class="tich-mt-4">{{ $students->links() }}</div>
        @endif
    </div>
@endsection

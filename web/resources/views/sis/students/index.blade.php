@extends('layouts.sis')

@section('sis-content')
    <x-page-toolbar title="Student records" meta="360° student biodata from admissions and enrollment">
        <x-slot:filters>
            <form method="GET" action="{{ route('sis.students.index') }}" class="tich-page-toolbar__filters-form">
                @include('partials.search-field', ['placeholder' => 'Name, reg no., application no., email', 'value' => $filters['search'] ?? ''])
                <select id="status" name="status" class="tich-input tich-input--compact">
                    <option value="">All</option>
                    @foreach (['pending', 'active', 'deferred', 'suspended', 'withdrawn', 'graduated', 'alumni'] as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                <select id="program_id" name="program_id" class="tich-input tich-input--compact">
                    <option value="">All programmes</option>
                    @foreach ($programs as $program)
                        <option value="{{ $program->id }}" @selected((string) ($filters['program_id'] ?? '') === (string) $program->id)>
                            {{ $program->program_code }} - {{ $program->program_name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </x-slot:filters>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Reg. number</th>
                    <th>Student</th>
                    <th>Programme</th>
                    <th>Campus</th>
                    <th>Status</th>
                    <th>Portal</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($students as $student)
                    <tr>
                        <td>{{ $student->registration_number }}</td>
                        <td>
                            {{ $student->applicant?->fullName() ?? '-' }}<br>
                            <span class="tich-caption">{{ $student->applicant?->email }}</span>
                        </td>
                        <td>{{ $student->program?->program_name ?? '-' }}</td>
                        <td>{{ $student->campus?->campus_name ?? '-' }}</td>
                        <td>{{ ucfirst($student->enrollment_status) }}</td>
                        <td>
                            @if ($student->user_id)
                                <span class="tich-caption">Active account</span>
                            @elseif ($student->hasActivePortalInvite())
                                <span class="tich-caption">Invite pending</span>
                            @else
                                <span class="tich-caption">-</span>
                            @endif
                        </td>
                        <td><a href="{{ route('sis.students.show', $student) }}" class="tich-link">View 360°</a></td>
                    </tr>
                @empty
                    @include('partials.states.table-empty', ['colspan' => 7, 'title' => 'No student records yet', 'icon' => 'inbox'])
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="tich-mt-6">{{ $students->links() }}</div>
@endsection

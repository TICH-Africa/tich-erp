@extends('layouts.sis')

@section('sis-content')
    <div class="tich-section__intro" style="text-align: left;">
        <h1 class="tich-h1" style="font-size: 2rem;">Student records</h1>
        <p class="tich-text">Centralized 360° student biodata compiled from admissions and enrollment records.</p>
    </div>

    <form method="GET" action="{{ route('sis.students.index') }}" class="tich-card tich-mb-8" style="padding: 1.5rem;">
        <div class="tich-grid tich-grid--3" style="gap: 1rem;">
            <div class="tich-form-group" style="margin: 0;">
                <label for="search" class="tich-label">Search</label>
                <input type="text" id="search" name="search" value="{{ $filters['search'] ?? '' }}" class="tich-input" placeholder="Name, reg no., application no., email">
            </div>
            <div class="tich-form-group" style="margin: 0;">
                <label for="status" class="tich-label">Enrollment status</label>
                <select id="status" name="status" class="tich-input">
                    <option value="">All</option>
                    @foreach (['pending', 'active', 'deferred', 'suspended', 'withdrawn', 'graduated', 'alumni'] as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group" style="margin: 0;">
                <label for="program_id" class="tich-label">Programme</label>
                <select id="program_id" name="program_id" class="tich-input">
                    <option value="">All programmes</option>
                    @foreach ($programs as $program)
                        <option value="{{ $program->id }}" @selected((string) ($filters['program_id'] ?? '') === (string) $program->id)>
                            {{ $program->program_code }} - {{ $program->program_name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="tich-mt-4">
            <button type="submit" class="tich-btn tich-btn-primary">Filter</button>
            <a href="{{ route('sis.students.index') }}" class="tich-btn tich-btn-secondary">Reset</a>
        </div>
    </form>

    <div class="tich-card" style="overflow-x: auto;">
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
                    <tr>
                        <td colspan="7" style="padding: 2rem; text-align: center;" class="tich-text">No student records yet. Students are created when applications are approved.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="tich-mt-6">{{ $students->links() }}</div>
@endsection

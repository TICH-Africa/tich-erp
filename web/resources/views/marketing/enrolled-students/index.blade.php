@extends('layouts.marketing')

@section('title', 'Enrolled Students')

@section('department-content')
    <x-page-toolbar title="Enrolled Students" meta="Read-only view of enrolled students across intakes" />

    <div class="tich-card tich-mt-4 tich-mb-4" style="padding: 1rem;">
        <form method="GET" class="tich-form-grid tich-form-grid--4" style="gap: 1rem;">
            <div class="tich-form-group">
                <label class="tich-label" for="intake">Intake</label>
                <select id="intake" name="intake" class="tich-input">
                    <option value="">All Intakes</option>
                    @foreach ($intakes as $intake)
                        <option value="{{ $intake }}" {{ ($filters['intake'] ?? '') === $intake ? 'selected' : '' }}>{{ $intake }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="program_id">Programme</label>
                <select id="program_id" name="program_id" class="tich-input">
                    <option value="">All Programmes</option>
                    @foreach ($programs as $program)
                        <option value="{{ $program->id }}" {{ ($filters['program_id'] ?? '') == $program->id ? 'selected' : '' }}>{{ $program->program_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="status">Status</label>
                <select id="status" name="status" class="tich-input">
                    <option value="">All Statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label">&nbsp;</label>
                <button type="submit" class="tich-btn tich-btn-primary">Filter</button>
                <a href="{{ route('marketing.enrolled-students.index') }}" class="tich-btn tich-btn-secondary">Clear</a>
            </div>
        </form>
    </div>

    <div class="tich-card tich-mt-4 tich-mb-4">
        <a href="{{ route('marketing.enrolled-students.compare') }}" class="tich-btn tich-btn-secondary">Compare Intakes</a>
    </div>

    <div class="tich-card tich-table-panel">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Reg No</th>
                    <th>Name</th>
                    <th>Programme</th>
                    <th>Intake</th>
                    <th>Campus</th>
                    <th>Status</th>
                    <th>Admission Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($students as $student)
                    <tr>
                        <td>{{ $student->registration_number }}</td>
                        <td>{{ $student->fullName() }}</td>
                        <td>{{ $student->program?->program_name ?? '-' }}</td>
                        <td>{{ $student->cohort_intake ?? '-' }}</td>
                        <td>{{ $student->campus?->name ?? '-' }}</td>
                        <td>{{ ucfirst($student->enrollment_status) }}</td>
                        <td>{{ $student->date_of_admission?->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">No students found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $students->links() }}
@endsection

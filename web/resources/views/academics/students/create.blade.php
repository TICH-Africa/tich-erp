@extends('layouts.academics')

@section('title', 'Add Academic Record')

@section('academics-content')
    <x-page-toolbar title="Add Academic Record" meta="HOD - Enroll student in programme and enter initial record" />

    <form method="POST" action="{{ route('academics.students.store') }}">
        @csrf

        <article class="tich-card tich-mt-6">
            <h3 class="tich-h3">Select Student</h3>
            <div class="tich-grid tich-grid--2 tich-mt-3">
                <div>
                    <label class="tich-label">Student *</label>
                    <select name="student_id" class="tich-input" required id="student-select">
                        <option value="">Select student</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}">{{ $student->registration_number }} - {{ $student->fullName() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </article>

        <article class="tich-card tich-mt-6">
            <h3 class="tich-h3">Record Details</h3>
            <div class="tich-mt-4">
                <table class="tich-doc-table" style="margin:0;">
                    <thead>
                        <tr>
                            <th>Field</th>
                            <th>Value</th>
                            <th>Field</th>
                            <th>Value</th>
                            <th>Field</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Programme *</strong></td>
                            <td>
                                <select name="program_id" class="tich-input" required style="width:100%;">
                                    <option value="">Select programme</option>
                                    @foreach ($programs as $program)
                                        <option value="{{ $program->id }}">{{ $program->program_code }} - {{ $program->program_name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><strong>Enrollment Date *</strong></td>
                            <td><input type="date" name="enrollment_date" class="tich-input" style="width:100%;" required></td>
                            <td><strong>Status *</strong></td>
                            <td>
                                <select name="status" class="tich-input" required style="width:100%;">
                                    <option value="active">Active</option>
                                    <option value="completed">Completed</option>
                                    <option value="withdrawn">Withdrawn</option>
                                    <option value="deferred">Deferred</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>GPA</strong></td>
                            <td><input type="number" step="0.01" min="0" max="4" name="gpa" class="tich-input" style="width:100%;"></td>
                            <td><strong>Units Registered</strong></td>
                            <td><input type="number" min="0" name="units_registered" class="tich-input" value="0" style="width:100%;"></td>
                            <td><strong>Units Completed</strong></td>
                            <td><input type="number" min="0" name="units_completed" class="tich-input" value="0" style="width:100%;"></td>
                        </tr>
                        <tr>
                            <td><strong>Entry Pathway</strong></td>
                            <td colspan="5"><input type="text" name="entry_pathway" class="tich-input" placeholder="Direct entry, Transfer, etc." style="width:100%;"></td>
                        </tr>
                        <tr>
                            <td><strong>Notes</strong></td>
                            <td colspan="5"><input type="text" name="notes" class="tich-input" placeholder="Optional notes" style="width:100%;"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </article>

        <div class="tich-grid tich-grid--2 tich-mt-6">
            <button type="submit" class="tich-btn tich-btn-primary">Save Record</button>
            <a href="{{ route('academics.students.index') }}" class="tich-btn tich-btn-secondary">Cancel</a>
        </div>
    </form>
@endsection

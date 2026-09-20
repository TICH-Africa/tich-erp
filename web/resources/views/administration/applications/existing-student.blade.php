@extends('layouts.administration')

@section('title', 'Existing Student Registration')

@section('administration-content')
    <x-page-toolbar title="Add Existing Student" meta="Register an existing student into a program" />

    <div class="tich-card tich-mt-6">
        <form method="POST" action="{{ route('administration.applications.existing-student.store') }}">
            @csrf

            <h3 class="tich-h3">Student Information</h3>
            <div class="tich-grid tich-grid--3 tich-mt-3">
                <div>
                    <label class="tich-label">Registration Number *</label>
                    <input type="text" name="registration_number" class="tich-input" required placeholder="e.g. S2026-0001">
                </div>
                <div>
                    <label class="tich-label">First Name *</label>
                    <input type="text" name="first_name" class="tich-input" required>
                </div>
                <div>
                    <label class="tich-label">Middle Name</label>
                    <input type="text" name="middle_name" class="tich-input">
                </div>
                <div>
                    <label class="tich-label">Surname *</label>
                    <input type="text" name="surname" class="tich-input" required>
                </div>
                <div>
                    <label class="tich-label">Email *</label>
                    <input type="email" name="email" class="tich-input" required>
                </div>
                <div>
                    <label class="tich-label">Phone Number</label>
                    <input type="text" name="phone_number" class="tich-input">
                </div>
            </div>

            <h3 class="tich-h3 tich-mt-6">Program Details</h3>
            <div class="tich-grid tich-grid--3 tich-mt-3">
                <div>
                    <label class="tich-label">Programme *</label>
                    <select name="program_id" class="tich-input" required>
                        <option value="">Select programme</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}">{{ $program->program_code }} - {{ $program->program_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="tich-label">Year Joined *</label>
                    <input type="date" name="year_joined" class="tich-input" required>
                </div>
                <div>
                    <label class="tich-label">Program Type *</label>
                    <select name="program_type" class="tich-input" required>
                        <option value="diploma">Diploma</option>
                        <option value="certificate">Certificate</option>
                    </select>
                </div>
                <div>
                    <label class="tich-label">Duration (months)</label>
                    <input type="number" name="duration_months" class="tich-input" value="36">
                </div>
                <div>
                    <label class="tich-label">Semester</label>
                    <select name="semester" class="tich-input">
                        <option value="first">First</option>
                        <option value="second">Second</option>
                        <option value="third">Third</option>
                    </select>
                </div>
                <div>
                    <label class="tich-label">Campus</label>
                    <select name="campus_id" class="tich-input" required>
                        <option value="">Select campus</option>
                        @foreach ($campuses as $campus)
                            <option value="{{ $campus->id }}">{{ $campus->campus_code }} - {{ $campus->campus_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="tich-grid tich-grid--2 tich-mt-3">
                <div>
                    <label class="tich-label">Notes</label>
                    <input type="text" name="notes" class="tich-input">
                </div>
            </div>

            <div class="tich-grid tich-grid--2 tich-mt-6">
                <button type="submit" class="tich-btn tich-btn-primary">Register Student</button>
            </div>
        </form>
    </div>
@endsection

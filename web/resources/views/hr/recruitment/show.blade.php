@extends('layouts.hr')

@section('title', 'Application ' . $application->application_number)

@section('hr-content')
    <div class="tich-mb-8">
        <a href="{{ route('hr.recruitment.index') }}" class="tich-btn tich-btn-ghost">&larr; Back to applications</a>
    </div>

    <div class="tich-grid tich-grid--2 tich-mb-8">
        <article class="tich-card">
            <h3 class="tich-h3">Applicant Information</h3>
            <div class="tich-mt-4">
                <p><strong>Application No.:</strong> {{ $application->application_number }}</p>
                <p><strong>Full Name:</strong> {{ $application->full_name }}</p>
                <p><strong>ID Number:</strong> {{ $application->id_number }}</p>
                <p><strong>Date of Birth:</strong> {{ $application->date_of_birth?->format('Y-m-d') }}</p>
                <p><strong>Gender:</strong> {{ $application->gender }}</p>
                <p><strong>Marital Status:</strong> {{ $application->marital_status ?? 'N/A' }}</p>
                <p><strong>Email:</strong> {{ $application->email }}</p>
                <p><strong>Phone:</strong> {{ $application->phone_number }}</p>
                <p><strong>Postal Address:</strong> {{ $application->postal_address ?? 'N/A' }}</p>
                <p><strong>Physical Address:</strong> {{ $application->physical_address ?? 'N/A' }}</p>
            </div>
        </article>

        <article class="tich-card">
            <h3 class="tich-h3">Education & Experience</h3>
            <div class="tich-mt-4">
                <p><strong>Highest Qualification:</strong>
                    {{ $application->highest_qualification }}
                    @if ($application->highest_qualification === 'Other' && $application->qualification_other)
                        ({{ $application->qualification_other }})
                    @endif
                </p>
                <p><strong>Institution:</strong> {{ $application->institution }}</p>
                <p><strong>Year Completed:</strong> {{ $application->year_completed }}</p>
                <p><strong>Grade:</strong> {{ $application->grade ?? 'N/A' }}</p>
                <p><strong>Years of Experience:</strong> {{ $application->years_of_experience }}</p>
                <p><strong>Current Organization:</strong> {{ $application->current_organization ?? 'N/A' }}</p>
                <p><strong>Area of Specialization:</strong> {{ $application->area_of_specialization ?? 'N/A' }}</p>
                <p><strong>Expected Salary:</strong> {{ $application->expected_salary ?? 'N/A' }}</p>
                <p><strong>Notice Period:</strong> {{ $application->notice_period ?? 'N/A' }}</p>
            </div>
        </article>
    </div>

    <div class="tich-card tich-mb-8">
        <h3 class="tich-h3">Documents</h3>
        <div class="tich-mt-4">
            <p><strong>CV/Resume:</strong> <a href="{{ asset('storage/' . $application->cv_file_path) }}" target="_blank">Download CV</a></p>
            @if ($application->cover_letter_file_path)
                <p><strong>Cover Letter:</strong> <a href="{{ asset('storage/' . $application->cover_letter_file_path) }}" target="_blank">Download Cover Letter</a></p>
            @endif
            @if ($application->certificates_file_paths)
                <p><strong>Certificates:</strong></p>
                <ul>
                    @foreach ($application->certificates_file_paths as $cert)
                        <li><a href="{{ asset('storage/' . $cert) }}" target="_blank">Download</a></li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="tich-card">
        <h3 class="tich-h3">Actions</h3>
        <div class="tich-mt-4">
            <form method="POST" action="{{ route('hr.recruitment.shortlist', $application) }}" class="tich-d-inline">
                @csrf
                <button type="submit" class="tich-btn tich-btn-primary">Shortlist</button>
            </form>

            <form method="POST" action="{{ route('hr.recruitment.approve', $application) }}" class="tich-d-inline tich-ml-4">
                @csrf
                <button type="submit" class="tich-btn tich-btn-success">Approve & Offer</button>
            </form>

            @if ($application->decision == 'approved')
                <form method="POST" action="{{ route('hr.recruitment.send-qualified-email', $application) }}" class="tich-d-inline tich-ml-4">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-secondary">Email Qualified Status</button>
                </form>
            @endif

            <button type="button" onclick="document.getElementById('reject-form').style.display='block'" class="tich-btn tich-btn-danger tich-ml-4">Reject</button>

            <div id="reject-form" style="display: none; margin-top: 1rem;">
                <form method="POST" action="{{ route('hr.recruitment.reject', $application) }}">
                    @csrf
                    <textarea name="rejection_reason" placeholder="Enter rejection reason..." class="tich-input" rows="3" required></textarea>
                    <button type="submit" class="tich-btn tich-btn-danger tich-mt-2">Confirm Rejection</button>
                </form>
            </div>
        </div>
    </div>

    @if ($application->newStaff)
        <div class="tich-card tich-mt-6">
            <h3 class="tich-h3">Converted to Staff</h3>
            <div class="tich-mt-4">
                <p><strong>Employee Number:</strong> {{ $application->newStaff->employee_number }}</p>
                <p><strong>Name:</strong> {{ $application->newStaff->fullName() }}</p>
                <p><strong>Status:</strong> {{ ucfirst($application->newStaff->employment_status) }}</p>
                <a href="{{ route('hr.staff.show', $application->newStaff) }}" class="tich-btn tich-btn-ghost">View Staff Profile</a>
            </div>
        </div>
    @endif
@endsection

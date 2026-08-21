@extends('layouts.administration')

@section('title', 'Admission packages')

@section('administration-content')
    <x-page-toolbar title="Admission packages" meta="Upload the official admission letter and track enrolled students">
        <x-slot:actions>
            <a href="{{ $admissionsUrl }}" class="tich-btn tich-btn-secondary">Approved applications</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-mt-8">
        <h2 class="tich-h3">Admission letter template</h2>
        <p class="tich-text tich-mt-2">
            Upload the institution admission letter. When an application is accepted and the confirmation email is sent, this file is attached automatically.
        </p>

        @if ($letterExists)
            <div class="tich-flex-wrap tich-mt-4" style="gap:0.75rem; align-items:center;">
                <p class="tich-text" style="margin:0;">
                    Current file: <strong>{{ $letterFilename ?? 'admission-letter' }}</strong>
                </p>
                <a href="{{ route('administration.admission-packages.letter.download') }}" class="tich-btn tich-btn-secondary">Download</a>
                <form method="POST" action="{{ route('administration.admission-packages.letter.destroy') }}" onsubmit="return confirm('Remove the uploaded admission letter?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="tich-btn tich-btn-danger">Remove</button>
                </form>
            </div>
        @else
            <p class="tich-caption tich-mt-4">No admission letter uploaded yet. Approvals will still email confirmation, but without a letter attachment.</p>
        @endif

        <form method="POST" action="{{ route('administration.admission-packages.letter.store') }}" enctype="multipart/form-data" class="tich-form-stack tich-mt-6">
            @csrf
            <div class="tich-form-group">
                <label class="tich-label" for="admission_letter">{{ $letterExists ? 'Replace admission letter' : 'Upload admission letter' }}</label>
                <input type="file" id="admission_letter" name="admission_letter" class="tich-input" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp,application/pdf">
                <p class="tich-caption tich-mt-1">PDF, Word, or image — max 10 MB</p>
            </div>
            <button type="submit" class="tich-btn tich-btn-primary">{{ $letterExists ? 'Replace letter' : 'Upload letter' }}</button>
        </form>
    </div>

    <div class="tich-card tich-table-panel tich-mt-8">
        <h2 class="tich-h3">Enrolled students</h2>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Registration No.</th>
                        <th>Student</th>
                        <th>Programme</th>
                        <th>Admission date</th>
                        <th>Fee clearance</th>
                        <th>Enrollment</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        <tr>
                            <td><strong>{{ $student->registration_number ?? '-' }}</strong></td>
                            <td>{{ $student->fullName() }}</td>
                            <td class="tich-caption">{{ $student->program?->program_name ?? '-' }}</td>
                            <td class="tich-caption">{{ $student->date_of_admission?->format('d M Y') ?? '-' }}</td>
                            <td><span class="tich-badge">{{ ucfirst($student->fee_clearance_status ?? 'pending') }}</span></td>
                            <td class="tich-caption">{{ ucfirst(str_replace('_', ' ', $student->enrollment_status ?? 'unknown')) }}</td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 6, 'title' => 'No enrolled students yet', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($students instanceof \Illuminate\Contracts\Pagination\Paginator && $students->hasPages())
            <div class="tich-mt-4">{{ $students->links() }}</div>
        @endif
    </div>
@endsection

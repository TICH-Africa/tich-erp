@extends('layouts.administration')

@section('title', 'Admission packages')

@section('administration-content')
    <x-page-toolbar title="Admission packages" meta="Registration numbers and digital admission letters with fee attachments">
        <x-slot:actions>
            <a href="{{ $admissionsUrl }}" class="tich-btn tich-btn-secondary">Approved applications</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mt-8">
        <div class="tich-table-wrap">
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
                        <tr><td colspan="6" class="tich-table-empty">No enrolled students yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($students instanceof \Illuminate\Contracts\Pagination\Paginator && $students->hasPages())
            <div class="tich-mt-4">{{ $students->links() }}</div>
        @endif
    </div>
@endsection

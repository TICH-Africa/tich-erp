@extends('layouts.administration')

@section('title', 'Application framework')

@section('administration-content')
    <x-page-toolbar title="Application framework" meta="Online portal for digital document intake, bio-data capture, and bulk enrollment">
        <x-slot:actions>
            <a href="{{ $applyUrl }}" class="tich-btn tich-btn-primary" target="_blank" rel="noopener">Open apply portal</a>
            <a href="{{ $admissionsUrl }}" class="tich-btn tich-btn-secondary">Admissions queue</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--3 tich-mt-8">
        <article class="tich-card">
            <h3 class="tich-h4">Digital document intake</h3>
            <p class="tich-caption tich-mt-2">Applicants upload supporting documents through the public application portal.</p>
        </article>
        <article class="tich-card">
            <h3 class="tich-h4">Bio-data capture</h3>
            <p class="tich-caption tich-mt-2">Personal, academic, and next-of-kin details are captured at submission.</p>
        </article>
        <article class="tich-card">
            <h3 class="tich-h4">Bulk enrollment</h3>
            <p class="tich-caption tich-mt-2">Approved applications feed student records and registration workflows.</p>
        </article>
    </div>

    <div class="tich-card tich-table-panel tich-mt-8">
        <h2 class="tich-h3">Recent applications</h2>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Application No.</th>
                        <th>Applicant</th>
                        <th>Programme</th>
                        <th>Status</th>
                        <th>Fee paid</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($applications as $application)
                        <tr>
                            <td><strong>{{ $application->application_number }}</strong></td>
                            <td>{{ trim(($application->first_name ?? '').' '.($application->surname ?? '')) }}</td>
                            <td class="tich-caption">{{ $application->program?->program_name ?? '-' }}</td>
                            <td><span class="tich-badge">{{ str_replace('_', ' ', ucfirst($application->status ?? 'unknown')) }}</span></td>
                            <td>{{ ! empty($application->application_fee_paid) ? 'Yes' : 'No' }}</td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 5, 'title' => 'No applications found', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($applications instanceof \Illuminate\Contracts\Pagination\Paginator && $applications->hasPages())
            <div class="tich-mt-4">{{ $applications->links() }}</div>
        @endif
    </div>
@endsection

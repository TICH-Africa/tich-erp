@extends('layouts.administration')

@section('title', 'Application framework')

@section('administration-content')
    <x-page-toolbar title="Application framework" meta="View submitted applications and forward them to academics for review">
        <x-slot:actions>
            <a href="{{ $applyUrl }}" class="tich-btn tich-btn-primary" target="_blank" rel="noopener">Open apply portal</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--3 tich-mt-8">
        <article class="tich-card">
            <h3 class="tich-h4">View & forward</h3>
            <p class="tich-caption tich-mt-2">Administration views applications and forwards complete submissions to academics.</p>
        </article>
        <article class="tich-card">
            <h3 class="tich-h4">Academic review</h3>
            <p class="tich-caption tich-mt-2">Academics approve or reject with reasons in the Academics module.</p>
        </article>
        <article class="tich-card">
            <h3 class="tich-h4">Applicant package</h3>
            <p class="tich-caption tich-mt-2">After academic approval, applicants receive their letter, fee structure, and payment link.</p>
        </article>
    </div>

    <div class="tich-card tich-table-panel tich-mt-8">
        <h2 class="tich-h3">Applications</h2>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Application No.</th>
                        <th>Applicant</th>
                        <th>Programme</th>
                        <th>Status</th>
                        <th>Application Fee Paid</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($applications as $application)
                        <tr>
                            <td><strong>{{ $application->application_number }}</strong></td>
                            <td>{{ trim(($application->first_name ?? '').' '.($application->surname ?? '')) }}</td>
                            <td class="tich-caption">{{ $application->program?->program_name ?? '-' }}</td>
                            <td>@include('applications.partials.status-badge', ['applicant' => $application])</td>
                            <td>{{ ! empty($application->application_fee_paid) ? 'Yes' : 'No' }}</td>
                            <td>
                                <a href="{{ route('administration.applications.show', $application->id) }}" class="tich-link">View</a>
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 6, 'title' => 'No applications found', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($applications instanceof \Illuminate\Contracts\Pagination\Paginator && $applications->hasPages())
            <div class="tich-mt-4">{{ $applications->links() }}</div>
        @endif
    </div>
@endsection

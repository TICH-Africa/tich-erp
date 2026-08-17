@extends('layouts.administration')

@section('title', 'Automated lifecycle')

@section('administration-content')
    <x-page-toolbar title="Automated lifecycle" meta="5-step workflow: Submission → Academic verification → Payment (M-Pesa) → Admin approval → Letter generation">
        <x-slot:actions>
            <a href="{{ $admissionsUrl }}" class="tich-btn tich-btn-secondary">Admissions dashboard</a>
            <a href="{{ $mpesaUrl }}" class="tich-btn tich-btn-ghost">M-Pesa settings</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-stat-row tich-stat-row--5 tich-mt-8">
        <div class="tich-stat">
            <p class="tich-stat__label">1. Submission</p>
            <p class="tich-stat__value">{{ $stats['submission'] }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">2. Academic verification</p>
            <p class="tich-stat__value">{{ $stats['academic_verification'] }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">3. Payment</p>
            <p class="tich-stat__value">{{ $stats['payment'] }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">4. Admin approval</p>
            <p class="tich-stat__value">{{ $stats['admin_approval'] }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">5. Letter generation</p>
            <p class="tich-stat__value">{{ $stats['letter_generation'] }}</p>
        </div>
    </div>

    <article class="tich-card tich-mt-8">
        <h2 class="tich-h3">How the pipeline works</h2>
        <ol class="tich-text tich-mt-4" style="padding-left: 1.25rem; display: grid; gap: 0.75rem;">
            <li><strong>Submission</strong> - Applicant completes the online portal with bio-data and documents.</li>
            <li><strong>Academic verification</strong> - Learning department reviews qualifications in Admissions.</li>
            <li><strong>Payment</strong> - Application / admission fees settle via M-Pesa Daraja (Finance settings).</li>
            <li><strong>Admin approval</strong> - Admissions / Administration authorize the offer.</li>
            <li><strong>Letter generation</strong> - Digital admission letter and fee attachments are issued with registration numbers.</li>
        </ol>
        <p class="tich-caption tich-mt-4">Total tracked applications: {{ $stats['total'] }}</p>
    </article>
@endsection

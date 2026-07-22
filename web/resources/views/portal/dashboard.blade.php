@extends('layouts.app')

@section('title', 'Student portal')

@section('content')
    <section class="tich-section">
        <div class="tich-container">
            @include('partials.alerts')

            <div class="tich-mb-8">
                <p class="tich-caption">Student portal</p>
                <h1 class="tich-h1">Welcome, {{ $biodata['identity']['full_name'] }}</h1>
                <p class="tich-text tich-mt-2">
                    {{ $student->registration_number }}
                    · {{ $biodata['academic']['program'] }}
                    · {{ ucfirst($student->enrollment_status) }}
                </p>
            </div>

            <div class="tich-grid tich-grid--3">
                <article class="tich-card">
                    <h3 class="tich-h3">My profile</h3>
                    <p class="tich-text tich-mt-2">
                        {{ $biodata['contact']['email'] }}<br>
                        {{ $biodata['contact']['phone_number'] ?? '—' }}
                    </p>
                    <p class="tich-caption tich-mt-3">{{ $biodata['contact']['home_county'] ?? '' }}</p>
                </article>

                <article class="tich-card">
                    <h3 class="tich-h3">Application</h3>
                    <p class="tich-text tich-mt-2">
                        <strong>{{ $biodata['application']['application_number'] }}</strong><br>
                        Status: {{ ucwords(str_replace('_', ' ', $biodata['application']['status'] ?? '')) }}<br>
                        Review: {{ ucwords(str_replace('_', ' ', $biodata['application']['academic_review_status'] ?? '')) }}
                    </p>
                    <a href="{{ route('apply.status', ['application_number' => $biodata['application']['application_number'], 'email' => $biodata['contact']['email']]) }}" class="tich-btn tich-btn-secondary tich-mt-4">Check application status</a>
                </article>

                <article class="tich-card">
                    <h3 class="tich-h3">Enrolment</h3>
                    <p class="tich-text tich-mt-2">
                        Campus: {{ $biodata['enrollment']['campus'] ?? '—' }}<br>
                        Admitted: {{ $biodata['enrollment']['date_of_admission'] ?? '—' }}<br>
                        Fee clearance: {{ ucfirst($biodata['enrollment']['fee_clearance_status'] ?? 'pending') }}
                    </p>
                </article>

                <article class="tich-card">
                    <h3 class="tich-h3">Documents</h3>
                    <p class="tich-text tich-mt-2">{{ $biodata['documents']->count() }} file(s) on record from your application.</p>
                </article>

                <article class="tich-card">
                    <h3 class="tich-h3">Academics</h3>
                    <p class="tich-text tich-mt-2">Unit registration, grades, and timetables will appear here as modules are enabled.</p>
                </article>

                <article class="tich-card">
                    <h3 class="tich-h3">Finance</h3>
                    <p class="tich-text tich-mt-2">
                        Balance: KES {{ number_format((float) ($biodata['enrollment']['overall_balance'] ?? 0), 2) }}
                    </p>
                    <p class="tich-caption tich-mt-2">Invoices and payment history coming soon.</p>
                </article>
            </div>
        </div>
    </section>
@endsection

@extends('layouts.app')

@section('title', 'Admissions')

@section('content')
<section class="tich-section">
    <div class="tich-container">
        <h1 class="tich-h1">Admissions dashboard</h1>
        <p class="tich-text tich-mt-4">Applicant intake and onboarding — full workflow coming soon.</p>
        <a href="{{ route('week4.applications.list') }}" class="tich-btn tich-btn-primary tich-mt-6">View applications</a>
    </div>
</section>
@endsection

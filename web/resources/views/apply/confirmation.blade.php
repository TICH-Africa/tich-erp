@extends('layouts.app')

@section('title', 'Application submitted')

@section('content')
<section class="tich-section">
    <div class="tich-container" style="max-width: 36rem; text-align: center;">
        <div class="tich-card">
            <h1 class="tich-h2">Application submitted</h1>
            <p class="tich-text tich-mt-4">Thank you. Your application has been received and queued for academic department review.</p>

            <p class="tich-stat tich-mt-8">
                <span class="tich-stat__label">Application number</span>
                <span class="tich-stat__value" style="font-size: 1.5rem;">{{ $applicationNumber }}</span>
            </p>

            @if ($email)
                <p class="tich-caption tich-mt-4">Confirmation sent to {{ $email }}. Keep your application number for status checks.</p>
            @endif

            <div class="tich-flex-wrap tich-mt-8" style="justify-content: center;">
                <a href="{{ route('apply.status') }}" class="tich-btn tich-btn-primary">Check application status</a>
                <a href="{{ route('programs.index') }}" class="tich-btn tich-btn-blue">Back to programmes</a>
            </div>
        </div>
    </div>
</section>
@endsection

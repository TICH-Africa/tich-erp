@extends('layouts.employee')

@section('title', 'Acknowledge policy')

@section('employee-content')
    <x-page-toolbar title="Acknowledge policy" :meta="$policy->title . ' · v' . $policy->version">
        <x-slot:actions>
            <a href="{{ route('policies.assigned') }}" class="tich-btn tich-btn-ghost">← Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-mb-6" style="background:#f8fafc; border-left:4px solid #2563eb;">
        <h2 class="tich-h3">Policy acknowledgement</h2>
        <p class="tich-text tich-mt-2 tich-text--secondary">Please read the policy document below and confirm your acknowledgement.</p>
        <div class="tich-grid tich-grid--2 tich-mt-4">
            <div>
                <strong>Title:</strong> {{ $policy->title }}
            </div>
            <div>
                <strong>Version:</strong> v{{ $policy->version }}
            </div>
            <div>
                <strong>Effective Date:</strong> {{ $policy->effective_date?->format('Y-m-d') ?? '—' }}
            </div>
            <div>
                <strong>Category:</strong> {{ ucfirst($policy->category) }}
            </div>
        </div>
    </div>

    <article class="tich-card tich-mb-6" style="padding: 0; overflow: hidden;">
        @if (str_contains($policy->mime_type, 'pdf'))
            <iframe src="{{ route('policies.view', $policy) }}" style="width: 100%; height: 70vh; border: none;" title="{{ $policy->title }}"></iframe>
        @elseif (str_contains($policy->mime_type, 'image'))
            <img src="{{ route('policies.view', $policy) }}" alt="{{ $policy->title }}" style="width: 100%; max-height: 70vh; object-fit: contain;">
        @elseif (str_contains($policy->original_filename, '.doc') || str_contains($policy->original_filename, '.docx'))
            <div style="padding: 60px; text-align: center;">
                <p class="tich-text tich-text--secondary">Word document preview is only available on a public server.</p>
                <a href="{{ route('policies.download', $policy) }}" class="tich-btn tich-btn-primary tich-mt-4" target="_blank">Download to view</a>
            </div>
        @else
            <div style="padding: 60px; text-align: center;">
                <p class="tich-text tich-text--secondary">Preview not available for this file type.</p>
                <a href="{{ route('policies.download', $policy) }}" class="tich-btn tich-btn-primary tich-mt-4" target="_blank">Download to view</a>
            </div>
        @endif
    </article>

    <article class="tich-card">
        <h2 class="tich-h3">Confirm acknowledgement</h2>
        <form method="POST" action="{{ route('policies.acknowledge.store', $policy) }}" class="tich-form-stack tich-mt-4">
            @csrf
            <div class="tich-grid tich-grid--2">
                <div>
                    <label for="full_name" class="tich-label">Full name *</label>
                    <input type="text" id="full_name" name="full_name" value="{{ old('full_name', $staff->fullName()) }}" required class="tich-input">
                </div>
                <div>
                    <label for="employee_number" class="tich-label">Employee number *</label>
                    <input type="text" id="employee_number" name="employee_number" value="{{ old('employee_number', $staff->employee_number) }}" required class="tich-input">
                </div>
                <div class="tich-grid--span-2">
                    <label for="signature" class="tich-label">Digital signature (optional)</label>
                    <input type="text" id="signature" name="signature" value="{{ old('signature') }}" class="tich-input" placeholder="Type your full name as digital signature">
                </div>
            </div>

            <div class="tich-mt-6">
                <button type="submit" class="tich-btn tich-btn-primary">I acknowledge this policy</button>
                <a href="{{ route('policies.assigned') }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </article>
@endsection
